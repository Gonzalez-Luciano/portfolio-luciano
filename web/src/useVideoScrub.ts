import { useEffect, useRef, useState } from 'react'
import MP4Box, { type MP4ArrayBuffer, type MP4File, type MP4Sample, type MP4VideoTrack } from 'mp4box'
import { clampProgress, nearestIndex, stepTowards } from '@/lib/scrub-math'

const LERP_TAU = 8
const SNAP = 0.002
const LRU_MAX = 24
const LEAD = 24
const WATCHDOG = 60000

type BankFrame = { ts: number; blob: Blob }

type Demuxed = { file: MP4File; track: MP4VideoTrack; samples: MP4Sample[] }

function demux(buffer: ArrayBuffer): Demuxed {
  const file = MP4Box.createFile()
  const samples: MP4Sample[] = []
  let track: MP4VideoTrack | undefined
  let parseError: string | undefined

  file.onError = (error) => {
    parseError = error
  }
  file.onReady = (info) => {
    track = info.videoTracks[0]
    if (!track) return
    file.setExtractionOptions(track.id, null, { nbSamples: Infinity })
    file.start()
  }
  file.onSamples = (_trackId, _user, batch) => {
    samples.push(...batch)
  }

  const chunk: MP4ArrayBuffer = Object.assign(buffer, { fileStart: 0 })
  file.appendBuffer(chunk)
  file.flush()

  if (parseError !== undefined) throw new Error(parseError)
  if (!track || samples.length === 0) throw new Error('No decodable video track')
  return { file, track, samples }
}

function codecDescription(file: MP4File, trackId: number): Uint8Array | undefined {
  const trak = file.getTrackById(trackId)
  for (const entry of trak.mdia.minf.stbl.stsd.entries) {
    const box = entry.avcC ?? entry.hvcC ?? entry.vpcC ?? entry.av1C
    if (box) {
      const stream = new MP4Box.DataStream(undefined, 0, MP4Box.DataStream.BIG_ENDIAN)
      box.write(stream)
      // Skip the 8-byte box header; WebCodecs wants only the configuration record.
      return new Uint8Array(stream.buffer, 8)
    }
  }
  return undefined
}

const settle = () => new Promise<void>((resolve) => window.setTimeout(resolve, 4))

async function buildFrameBank(
  buffer: ArrayBuffer,
  hardwareAcceleration: HardwareAcceleration,
  signal: AbortSignal,
  onDuration: (seconds: number) => void,
): Promise<BankFrame[]> {
  const { file, track, samples } = demux(buffer)
  onDuration(track.duration / track.timescale)

  const config: VideoDecoderConfig = {
    codec: track.codec,
    codedWidth: track.video.width,
    codedHeight: track.video.height,
    description: codecDescription(file, track.id),
    hardwareAcceleration,
  }
  const support = await VideoDecoder.isConfigSupported(config)
  if (!support.supported) throw new Error(`Unsupported decoder config for ${track.codec}`)

  const surface = document.createElement('canvas')
  surface.width = track.video.width
  surface.height = track.video.height
  const surfaceCtx = surface.getContext('2d')
  if (!surfaceCtx) throw new Error('2D context unavailable')

  const bank: BankFrame[] = []
  const state = { produced: 0, encoded: 0, failure: null as unknown }

  const decoder = new VideoDecoder({
    output: (frame) => {
      state.produced++
      const ts = frame.timestamp
      surfaceCtx.drawImage(frame, 0, 0, surface.width, surface.height)
      frame.close()
      // toBlob snapshots the bitmap synchronously, so the surface can be reused immediately.
      surface.toBlob(
        (blob) => {
          if (blob) bank.push({ ts, blob })
          state.encoded++
        },
        'image/webp',
        0.82,
      )
    },
    error: (error) => {
      state.failure = error
    },
  })

  const assertHealthy = () => {
    if (signal.aborted) throw signal.reason
    if (state.failure) throw state.failure
  }

  try {
    for (const sample of samples) {
      assertHealthy()
      // Throttle so decoding never runs more than LEAD frames ahead of blob encoding.
      while (decoder.decodeQueueSize + (state.produced - state.encoded) > LEAD) {
        await settle()
        assertHealthy()
      }
      decoder.decode(
        new EncodedVideoChunk({
          type: sample.is_sync ? 'key' : 'delta',
          timestamp: (sample.cts * 1e6) / sample.timescale,
          duration: (sample.duration * 1e6) / sample.timescale,
          data: sample.data,
        }),
      )
    }
    await decoder.flush()
    while (state.encoded < state.produced) {
      await settle()
      assertHealthy()
    }
    assertHealthy()
  } finally {
    if (decoder.state !== 'closed') decoder.close()
  }

  if (bank.length === 0) throw new Error('Frame bank is empty')
  return bank.sort((a, b) => a.ts - b.ts)
}

export function useVideoScrub(videoSrc: string) {
  const containerRef = useRef<HTMLDivElement>(null)
  const videoRef = useRef<HTMLVideoElement>(null)
  const canvasRef = useRef<HTMLCanvasElement>(null)
  const [scrollProgress, setScrollProgress] = useState(0)
  const [canvasLive, setCanvasLive] = useState(false)

  useEffect(() => {
    const container = containerRef.current
    const video = videoRef.current
    const canvas = canvasRef.current
    if (!container || !video || !canvas) return

    const ctx = canvas.getContext('2d')
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)')
    const abort = new AbortController()

    let bank: BankFrame[] = []
    const lru = new Map<number, ImageBitmap | null>()
    let current = 0
    let target = 0
    let ready = false
    let reverted = false
    let painted = false
    let building = false
    let dur = 0
    let span = 0
    let lastDrawn = -1
    let lastTime = performance.now()
    let rafId = 0
    let watchdogId: number | undefined
    let disposed = false

    const measure = () => {
      span = container.offsetHeight - window.innerHeight
    }
    const getProgress = () => clampProgress(window.scrollY, span)

    const onMetadata = () => {
      if (Number.isFinite(video.duration) && video.duration > 0) dur = video.duration
    }

    const request = (index: number) => {
      if (lru.has(index)) {
        // Refresh recency: Map iteration order is insertion order.
        const bitmap = lru.get(index) ?? null
        lru.delete(index)
        lru.set(index, bitmap)
        return
      }
      lru.set(index, null)
      createImageBitmap(bank[index].blob)
        .then((bitmap) => {
          if (disposed || !lru.has(index)) {
            bitmap.close()
            return
          }
          lru.set(index, bitmap)
        })
        .catch(() => {
          lru.delete(index)
        })
    }

    const warmLRU = (index: number) => {
      for (let j = index - 1; j <= index + 2; j++) {
        if (j >= 0 && j < bank.length) request(j)
      }
      for (const [key, bitmap] of lru) {
        if (lru.size <= LRU_MAX) break
        if (key >= index - 1 && key <= index + 2) continue
        lru.delete(key)
        bitmap?.close()
      }
    }

    const draw = () => {
      const index = nearestIndex(bank, current)
      if (index < 0) return
      warmLRU(index)
      if (index === lastDrawn) return
      const bitmap = lru.get(index)
      if (!bitmap || !ctx) return
      ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
      lastDrawn = index
      if (!painted) {
        painted = true
        setCanvasLive(true)
      }
    }

    const revert = () => {
      if (reverted) return
      reverted = true
      ready = false
      building = false
      painted = false
      lastDrawn = -1
      abort.abort()
      window.clearTimeout(watchdogId)
      for (const bitmap of lru.values()) bitmap?.close()
      lru.clear()
      bank = []
      if (!disposed) setCanvasLive(false)
    }

    const startBank = async () => {
      if (disposed || building || ready || reverted) return
      if (reducedMotion.matches || typeof VideoDecoder === 'undefined') return
      building = true
      watchdogId = window.setTimeout(revert, WATCHDOG)

      const onDuration = (seconds: number) => {
        if (dur <= 0 && seconds > 0) dur = seconds
      }

      try {
        const response = await fetch(videoSrc, { mode: 'cors', signal: abort.signal })
        if (!response.ok) throw new Error(`Video fetch failed with HTTP ${response.status}`)
        const buffer = await response.arrayBuffer()

        let frames: BankFrame[]
        try {
          frames = await buildFrameBank(buffer, 'prefer-hardware', abort.signal, onDuration)
        } catch (error) {
          if (abort.signal.aborted) throw error
          frames = await buildFrameBank(buffer, 'prefer-software', abort.signal, onDuration)
        }

        if (disposed || reverted) return
        window.clearTimeout(watchdogId)
        bank = frames
        building = false
        ready = true
      } catch {
        if (!disposed) revert()
      }
    }

    const tick = (now: number) => {
      const dt = Math.min(0.1, (now - lastTime) / 1000)
      lastTime = now

      const p = getProgress()
      setScrollProgress(p)

      if (dur > 0) {
        target = p * dur
        if (reducedMotion.matches) current = target
        else current = stepTowards(current, target, dt, LERP_TAU, SNAP)

        if (ready) draw()
        else if (!video.seeking && Math.abs(video.currentTime - current) > 0.01) {
          video.currentTime = current
        }
      }

      rafId = requestAnimationFrame(tick)
    }

    const onLoad = () => {
      void startBank()
    }

    measure()
    onMetadata()
    video.addEventListener('loadedmetadata', onMetadata)
    window.addEventListener('resize', measure)
    window.addEventListener('orientationchange', measure)
    if (document.readyState === 'complete') onLoad()
    else window.addEventListener('load', onLoad, { once: true })
    rafId = requestAnimationFrame(tick)

    return () => {
      disposed = true
      cancelAnimationFrame(rafId)
      window.clearTimeout(watchdogId)
      abort.abort()
      video.removeEventListener('loadedmetadata', onMetadata)
      window.removeEventListener('resize', measure)
      window.removeEventListener('orientationchange', measure)
      window.removeEventListener('load', onLoad)
      for (const bitmap of lru.values()) bitmap?.close()
      lru.clear()
    }
  }, [videoSrc])

  return { containerRef, videoRef, canvasRef, scrollProgress, canvasLive }
}
