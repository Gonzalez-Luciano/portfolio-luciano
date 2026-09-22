import { describe, expect, it } from 'vitest'
import { ANALYTICS_EVENTS } from '@/lib/analytics-events'

describe('analytics event contract', () => {
  it('defines exactly the four approved fixed event names', () => {
    expect(ANALYTICS_EVENTS).toEqual({
      cv: 'cv-download',
      github: 'github-click',
      linkedin: 'linkedin-click',
      email: 'email-click',
    })
    expect(Object.values(ANALYTICS_EVENTS)).toHaveLength(4)
    expect(Object.keys(ANALYTICS_EVENTS)).toHaveLength(4)
  })
})
