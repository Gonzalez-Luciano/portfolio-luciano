import { existsSync, statSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

const scroll = (file: string) => new URL(`../public/media/scroll/${file}`, import.meta.url)

describe('scroll scene media', () => {
  it('ships the upscaled video and its poster with a small footprint', () => {
    expect(existsSync(scroll('ink-tree-network-v3.mp4'))).toBe(true)
    expect(existsSync(scroll('ink-tree-network-v3-poster.webp'))).toBe(true)
    const videoSize = statSync(scroll('ink-tree-network-v3.mp4')).size
    expect(videoSize).toBeGreaterThan(1 * 1024 * 1024)
    expect(videoSize).toBeLessThan(3 * 1024 * 1024)
    const posterSize = statSync(scroll('ink-tree-network-v3-poster.webp')).size
    expect(posterSize).toBeGreaterThan(1 * 1024)
    expect(posterSize).toBeLessThan(200 * 1024)
  })
})
