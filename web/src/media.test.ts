import { existsSync, readFileSync, statSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

const scroll = (file: string) => new URL(`../public/media/scroll/${file}`, import.meta.url)
const publicAsset = (file: string) => new URL(`../public/${file}`, import.meta.url)

const pngDimensions = (file: string) => {
  const png = readFileSync(publicAsset(file))
  expect(png.subarray(0, 8)).toEqual(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]))
  expect(png.subarray(12, 16).toString('ascii')).toBe('IHDR')

  return {
    width: png.readUInt32BE(16),
    height: png.readUInt32BE(20),
  }
}

describe('scroll scene media', () => {
  it('ships the v4 video and its poster with a small footprint', () => {
    expect(existsSync(scroll('ink-tree-network-v4.mp4'))).toBe(true)
    expect(existsSync(scroll('ink-tree-network-v4-poster.webp'))).toBe(true)
    const videoSize = statSync(scroll('ink-tree-network-v4.mp4')).size
    expect(videoSize).toBeGreaterThan(1 * 1024 * 1024)
    expect(videoSize).toBeLessThan(3 * 1024 * 1024)
    const posterSize = statSync(scroll('ink-tree-network-v4-poster.webp')).size
    expect(posterSize).toBeGreaterThan(1 * 1024)
    expect(posterSize).toBeLessThan(200 * 1024)
  })

  it('ships the v4 poster as a lossy WebP at the video resolution (1920×1080)', () => {
    const poster = readFileSync(scroll('ink-tree-network-v4-poster.webp'))
    expect(poster.subarray(0, 4).toString('ascii')).toBe('RIFF')
    expect(poster.subarray(8, 16).toString('ascii')).toBe('WEBPVP8 ')
    expect(poster.subarray(23, 26)).toEqual(Buffer.from([0x9d, 0x01, 0x2a]))
    expect(poster.readUInt16LE(26) & 0x3fff).toBe(1920)
    expect(poster.readUInt16LE(28) & 0x3fff).toBe(1080)
  })

  it('serves the video with the moov atom first so playback can start before the download ends', () => {
    const head = readFileSync(scroll('ink-tree-network-v4.mp4')).subarray(0, 64)
    expect(head.subarray(4, 8).toString('ascii')).toBe('ftyp')
    expect(head.subarray(head.readUInt32BE(0) + 4, head.readUInt32BE(0) + 8).toString('ascii')).toBe('moov')
  })
})

describe('favicon package', () => {
  it('ships the four approved favicon assets unchanged', () => {
    const ico = readFileSync(publicAsset('favicon.ico'))
    expect(ico.length).toBeGreaterThan(0)
    expect(ico.subarray(0, 4)).toEqual(Buffer.from([0, 0, 1, 0]))

    expect(pngDimensions('favicon-16x16.png')).toEqual({ width: 16, height: 16 })
    expect(pngDimensions('favicon-32x32.png')).toEqual({ width: 32, height: 32 })
    expect(pngDimensions('apple-touch-icon.png')).toEqual({ width: 180, height: 180 })
  })

  it('does not ship excluded favicon package files', () => {
    expect(existsSync(publicAsset('site.webmanifest'))).toBe(false)
    expect(existsSync(publicAsset('android-chrome-192x192.png'))).toBe(false)
    expect(existsSync(publicAsset('android-chrome-512x512.png'))).toBe(false)
  })
})
