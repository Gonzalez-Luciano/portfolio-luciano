import { describe, expect, it } from 'vitest'
import { BACKDROP_WIDTH, LINKS, STARS } from '@/lib/constellation'

describe('constellation', () => {
  it('keeps at most 20 % of the stars in the left third, where the text column sits', () => {
    const left = STARS.filter((star) => star.x < BACKDROP_WIDTH / 3)
    expect(left.length / STARS.length).toBeLessThanOrEqual(0.2)
  })

  it('links only existing, distinct stars', () => {
    for (const [from, to] of LINKS) {
      expect(from).not.toBe(to)
      expect(STARS[from]).toBeDefined()
      expect(STARS[to]).toBeDefined()
    }
  })
})
