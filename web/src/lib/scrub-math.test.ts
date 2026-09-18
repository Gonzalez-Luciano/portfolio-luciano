import { describe, expect, it } from 'vitest'
import { blendFrames, clampProgress, nearestIndex, stepTowards } from '@/lib/scrub-math'

describe('clampProgress', () => {
  it('maps scroll position onto the track span', () => {
    expect(clampProgress(0, 4000)).toBe(0)
    expect(clampProgress(1000, 4000)).toBe(0.25)
    expect(clampProgress(4000, 4000)).toBe(1)
  })

  it('clamps overscroll and guards an empty span', () => {
    expect(clampProgress(-50, 4000)).toBe(0)
    expect(clampProgress(9000, 4000)).toBe(1)
    expect(clampProgress(100, 0)).toBe(0)
    expect(clampProgress(100, -10)).toBe(0)
  })
})

describe('stepTowards', () => {
  it('eases a fraction of the remaining distance based on dt and tau', () => {
    const next = stepTowards(0, 10, 0.016, 8, 0.002)
    expect(next).toBeCloseTo(10 * (1 - Math.exp(-0.016 * 8)), 10)
    expect(next).toBeGreaterThan(0)
    expect(next).toBeLessThan(10)
  })

  it('snaps to the target once within the snap distance', () => {
    expect(stepTowards(4.999, 5, 0.016, 8, 0.002)).toBe(5)
  })

  it('never jumps straight to a distant target', () => {
    expect(stepTowards(0, 8, 0.1, 8, 0.002)).toBeLessThan(8)
  })
})

describe('nearestIndex', () => {
  const frames = [0, 33_333, 66_666, 100_000].map((ts) => ({ ts }))

  it('returns -1 for an empty bank', () => {
    expect(nearestIndex([], 1)).toBe(-1)
  })

  it('finds the nearest timestamp in seconds', () => {
    expect(nearestIndex(frames, 0)).toBe(0)
    expect(nearestIndex(frames, 0.04)).toBe(1)
    expect(nearestIndex(frames, 0.06)).toBe(2)
    expect(nearestIndex(frames, 0.099)).toBe(3)
  })

  it('clamps outside the bank range', () => {
    expect(nearestIndex(frames, -1)).toBe(0)
    expect(nearestIndex(frames, 10)).toBe(3)
  })
})

describe('blendFrames', () => {
  const frames = [{ ts: 0 }, { ts: 62_500 }, { ts: 125_000 }]

  it('returns null for an empty bank', () => {
    expect(blendFrames([], 1)).toBeNull()
  })

  it('weights the two neighbours by the fractional position', () => {
    expect(blendFrames(frames, 0.03125)).toEqual({ index: 0, next: 1, weight: 0.5 })
    expect(blendFrames(frames, 0.0625)).toEqual({ index: 1, next: 2, weight: 0 })
  })

  it('clamps before the first and after the last frame', () => {
    expect(blendFrames(frames, -1)).toEqual({ index: 0, next: 0, weight: 0 })
    expect(blendFrames(frames, 9)).toEqual({ index: 2, next: 2, weight: 0 })
  })
})
