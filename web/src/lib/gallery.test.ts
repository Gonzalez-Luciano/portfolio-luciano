import { describe, expect, it } from 'vitest'
import { thumbnailSlots, wrapIndex } from '@/lib/gallery'

describe('thumbnailSlots', () => {
  it('shows no thumbnails for zero or one image', () => {
    expect(thumbnailSlots(0)).toEqual({ visible: 0, overflow: 0 })
    expect(thumbnailSlots(1)).toEqual({ visible: 0, overflow: 0 })
  })

  it('fills four tiles: every image up to four, otherwise three thumbnails and a +N tile', () => {
    expect(thumbnailSlots(3)).toEqual({ visible: 3, overflow: 0 })
    expect(thumbnailSlots(4)).toEqual({ visible: 4, overflow: 0 })
    expect(thumbnailSlots(5)).toEqual({ visible: 3, overflow: 2 })
    expect(thumbnailSlots(12)).toEqual({ visible: 3, overflow: 9 })
  })
})

describe('wrapIndex', () => {
  it('wraps previous and next around the gallery', () => {
    expect(wrapIndex(-1, 5)).toBe(4)
    expect(wrapIndex(5, 5)).toBe(0)
    expect(wrapIndex(2, 5)).toBe(2)
    expect(wrapIndex(3, 0)).toBe(0)
  })
})
