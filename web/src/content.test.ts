import { describe, expect, it } from 'vitest'
import { resolveLocale, uiCopy } from '@/content'

describe('resolveLocale', () => {
  it('uses the path prefix and defaults to Spanish', () => {
    expect(resolveLocale('/')).toBe('es')
    expect(resolveLocale('/es')).toBe('es')
    expect(resolveLocale('/en')).toBe('en')
    expect(resolveLocale('/en/')).toBe('en')
    expect(resolveLocale('/english')).toBe('es')
    expect(resolveLocale('/fr')).toBe('es')
  })
})

describe('uiCopy', () => {
  it('defines the same interface keys for both locales', () => {
    const keys = (value: object): string[] => Object.keys(value).sort()
    expect(keys(uiCopy('es'))).toEqual(keys(uiCopy('en')))
    expect(keys(uiCopy('es').menu)).toEqual(keys(uiCopy('en').menu))
  })
})
