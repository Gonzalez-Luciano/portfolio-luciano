import { describe, expect, it } from 'vitest'
import { pickActiveId } from '@/lib/active-section'

describe('pickActiveId', () => {
  it('returns null when nothing is intersecting', () => {
    expect(pickActiveId([])).toBeNull()
  })

  it('returns the single candidate', () => {
    expect(pickActiveId([{ id: 'experience', top: 120 }])).toBe('experience')
  })

  it('picks the candidate nearest the top of the band, regardless of array order', () => {
    const candidates = [
      { id: 'stack', top: 300 },
      { id: 'projects', top: -10 },
      { id: 'experience', top: 150 },
    ]
    expect(pickActiveId(candidates)).toBe('projects')
  })

  it('is deterministic when a scrollIntoView jump batches several crossings in one callback', () => {
    // IntersectionObserver does not guarantee entries() order; the topmost band member wins either way.
    const forward = [
      { id: 'about', top: 80 },
      { id: 'contact', top: 400 },
    ]
    const reversed = [...forward].reverse()
    expect(pickActiveId(forward)).toBe('about')
    expect(pickActiveId(reversed)).toBe('about')
  })
})
