/** Tiles under the main screenshot: every image up to four, otherwise three thumbnails plus a "+N" tile. */
export const GALLERY_THUMB_LIMIT = 4

export function thumbnailSlots(count: number, limit = GALLERY_THUMB_LIMIT): { visible: number; overflow: number } {
  if (count <= 1) return { visible: 0, overflow: 0 }
  if (count <= limit) return { visible: count, overflow: 0 }
  return { visible: limit - 1, overflow: count - (limit - 1) }
}

export function wrapIndex(index: number, count: number): number {
  if (count <= 0) return 0
  return ((index % count) + count) % count
}
