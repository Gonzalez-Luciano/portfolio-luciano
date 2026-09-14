import { describe, expect, it } from 'vitest'
import {
  sectionOneOpacity,
  sectionThreeOpacity,
  sectionTwoOpacity,
} from '@/lib/section-opacity'

describe('section opacity curves', () => {
  it('section 1 holds, then fades out between 0.20 and 0.28', () => {
    expect(sectionOneOpacity(0)).toBe(1)
    expect(sectionOneOpacity(0.19)).toBe(1)
    expect(sectionOneOpacity(0.24)).toBeCloseTo(0.5)
    expect(sectionOneOpacity(0.28)).toBeCloseTo(0)
    expect(sectionOneOpacity(0.5)).toBe(0)
  })

  it('section 2 fades in 0.32-0.40, holds, then fades out 0.55-0.63', () => {
    expect(sectionTwoOpacity(0.31)).toBe(0)
    expect(sectionTwoOpacity(0.36)).toBeCloseTo(0.5)
    expect(sectionTwoOpacity(0.45)).toBe(1)
    expect(sectionTwoOpacity(0.59)).toBeCloseTo(0.5)
    expect(sectionTwoOpacity(0.7)).toBe(0)
  })

  it('section 3 fades in 0.67-0.75 and holds', () => {
    expect(sectionThreeOpacity(0.66)).toBe(0)
    expect(sectionThreeOpacity(0.71)).toBeCloseTo(0.5)
    expect(sectionThreeOpacity(0.75)).toBe(1)
    expect(sectionThreeOpacity(1)).toBe(1)
  })

  it('sections are strictly sequential: never two visible at once', () => {
    for (let i = 0; i <= 1000; i++) {
      const p = i / 1000
      const visible = [sectionOneOpacity(p), sectionTwoOpacity(p), sectionThreeOpacity(p)].filter(
        (value) => value > 0,
      )
      expect(visible.length).toBeLessThanOrEqual(1)
    }
  })
})
