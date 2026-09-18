import { describe, expect, it } from 'vitest'
import { compact, emailAddress, formatMonth, formatPeriod, formatYears } from '@/lib/format'

describe('dates', () => {
  it('formats API months per locale', () => {
    expect(formatMonth('2025-09', 'es')).toBe('sept 2025')
    expect(formatMonth('2025-05', 'en')).toBe('May 2025')
  })

  it('formats current and closed periods', () => {
    expect(formatPeriod('2025-09', null, 'es')).toBe('sept 2025 – actualidad')
    expect(formatPeriod('2025-05', '2025-09', 'en')).toBe('May 2025 – Sep 2025')
  })

  it('formats optional education years', () => {
    expect(formatYears(null, null)).toBeNull()
    expect(formatYears(null, 2021)).toBe('2021')
    expect(formatYears(2022, 2022)).toBe('2022')
    expect(formatYears(2020, 2022)).toBe('2020 – 2022')
    expect(formatYears(2023, null)).toBe('2023')
  })
})

describe('emailAddress', () => {
  it('shows the address behind a mailto link', () => {
    expect(emailAddress('mailto:synthetic@example.test')).toBe('synthetic@example.test')
    expect(emailAddress('synthetic@example.test')).toBe('synthetic@example.test')
  })
})

describe('compact', () => {
  it('filters out null, undefined and empty-string values while preserving order', () => {
    expect(compact(['a', null, 'b', undefined, '', 'c'])).toEqual(['a', 'b', 'c'])
    expect(compact([null, undefined, ''])).toEqual([])
    expect(compact(['only'])).toEqual(['only'])
  })
})
