import { describe, expect, it } from 'vitest'
import {
  VEIL_OPACITY,
  columnVeilOpacity,
  correctionEnvelope,
  correctionFilter,
  landscapeOpacity,
  linksOpacity,
  navVeilOpacity,
  starsOpacity,
} from '@/lib/scene-timeline'

describe('transition correction', () => {
  it('is a triangle over 0.55–0.75 peaking at 0.65', () => {
    expect(correctionEnvelope(0.5)).toBe(0)
    expect(correctionEnvelope(0.55)).toBe(0)
    expect(correctionEnvelope(0.6)).toBeCloseTo(0.5)
    expect(correctionEnvelope(0.65)).toBeCloseTo(1)
    expect(correctionEnvelope(0.7)).toBeCloseTo(0.5)
    expect(correctionEnvelope(0.75)).toBe(0)
  })

  it('maps the envelope to the approved CSS filter', () => {
    expect(correctionFilter(0.2)).toBe('none')
    expect(correctionFilter(0.65)).toBe('contrast(1.35) saturate(1.25) brightness(0.94)')
    expect(correctionFilter(0.6)).toBe('contrast(1.175) saturate(1.125) brightness(0.97)')
  })
})

describe('veils', () => {
  it('column veil rises 0.66–0.70, holds to 0.78 and fades by 0.84', () => {
    expect(columnVeilOpacity(0.65)).toBe(0)
    expect(columnVeilOpacity(0.68)).toBeCloseTo(VEIL_OPACITY / 2)
    expect(columnVeilOpacity(0.74)).toBeCloseTo(VEIL_OPACITY)
    expect(columnVeilOpacity(0.81)).toBeCloseTo(VEIL_OPACITY / 2)
    expect(columnVeilOpacity(0.9)).toBe(0)
  })

  it('nav veil does not exist before the nav turns light at 0.70', () => {
    expect(navVeilOpacity(0.69)).toBe(0)
    expect(navVeilOpacity(0.7)).toBeCloseTo(VEIL_OPACITY)
    expect(navVeilOpacity(0.78)).toBeCloseTo(VEIL_OPACITY)
    expect(navVeilOpacity(0.84)).toBe(0)
  })
})

describe('backdrop', () => {
  it('fades the landscape into stars over 0.50–0.75 and draws links over 0.75–0.90', () => {
    expect(landscapeOpacity(0.4)).toBe(1)
    expect(landscapeOpacity(0.625)).toBeCloseTo(0.5)
    expect(landscapeOpacity(0.8)).toBe(0)
    expect(starsOpacity(0.4)).toBe(0)
    expect(starsOpacity(0.625)).toBeCloseTo(0.5)
    expect(starsOpacity(0.8)).toBe(1)
    expect(linksOpacity(0.74)).toBe(0)
    expect(linksOpacity(0.825)).toBeCloseTo(0.5)
    expect(linksOpacity(0.95)).toBe(1)
  })
})
