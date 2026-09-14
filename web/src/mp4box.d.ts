// Minimal typings for the parts of mp4box 0.5.x used by the frame bank.
declare module 'mp4box' {
  export interface MP4ArrayBuffer extends ArrayBuffer {
    fileStart: number
  }

  export interface MP4VideoTrack {
    id: number
    codec: string
    timescale: number
    duration: number
    nb_samples: number
    video: { width: number; height: number }
  }

  export interface MP4Info {
    duration: number
    timescale: number
    videoTracks: MP4VideoTrack[]
  }

  export interface MP4Sample {
    number: number
    track_id: number
    timescale: number
    cts: number
    dts: number
    duration: number
    is_sync: boolean
    data: Uint8Array
  }

  export interface MP4WritableBox {
    write(stream: DataStream): void
  }

  export interface MP4SampleEntry {
    avcC?: MP4WritableBox
    hvcC?: MP4WritableBox
    vpcC?: MP4WritableBox
    av1C?: MP4WritableBox
  }

  export interface MP4Trak {
    mdia: { minf: { stbl: { stsd: { entries: MP4SampleEntry[] } } } }
  }

  export interface MP4File {
    onReady?: (info: MP4Info) => void
    onError?: (error: string) => void
    onSamples?: (trackId: number, user: unknown, samples: MP4Sample[]) => void
    appendBuffer(data: MP4ArrayBuffer): number
    setExtractionOptions(
      trackId: number,
      user?: unknown,
      options?: { nbSamples?: number; rapAlignement?: boolean },
    ): void
    getTrackById(trackId: number): MP4Trak
    start(): void
    stop(): void
    flush(): void
  }

  export class DataStream {
    constructor(arrayBuffer?: ArrayBuffer, byteOffset?: number, endianness?: boolean)
    static BIG_ENDIAN: boolean
    static LITTLE_ENDIAN: boolean
    buffer: ArrayBuffer
  }

  export function createFile(): MP4File

  const MP4Box: {
    createFile: typeof createFile
    DataStream: typeof DataStream
  }

  export default MP4Box
}
