import { existsSync, statSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

const scroll = (file: string) => new URL(`../public/media/scroll/${file}`, import.meta.url)

describe('scroll scene media', () => {
  it('ships the video and its poster with a small footprint', () => {
    expect(existsSync(scroll('ink-tree-network-v2.mp4'))).toBe(true)
    expect(existsSync(scroll('ink-tree-network-v2-poster.webp'))).toBe(true)
    expect(statSync(scroll('ink-tree-network-v2.mp4')).size).toBeLessThan(3 * 1024 * 1024)
    expect(statSync(scroll('ink-tree-network-v2-poster.webp')).size).toBeLessThan(200 * 1024)
  })
})
