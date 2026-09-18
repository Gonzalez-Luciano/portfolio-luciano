import { describe, expect, it } from 'vitest'
import { alternateLocale, localePath, resolveLocale, uiCopy } from '@/content'

function shape(value: unknown): unknown {
  if (typeof value === 'function') return 'function'
  if (typeof value === 'object' && value !== null) {
    return Object.fromEntries(
      Object.keys(value)
        .sort()
        .map((key) => [key, shape((value as Record<string, unknown>)[key])]),
    )
  }
  return typeof value
}

describe('locale routing', () => {
  it('uses the path prefix and defaults to Spanish', () => {
    expect(resolveLocale('/')).toBe('es')
    expect(resolveLocale('/es')).toBe('es')
    expect(resolveLocale('/en')).toBe('en')
    expect(resolveLocale('/en/')).toBe('en')
    expect(resolveLocale('/english')).toBe('es')
  })

  it('links to the other language keeping the current anchor', () => {
    expect(alternateLocale('es')).toBe('en')
    expect(alternateLocale('en')).toBe('es')
    expect(localePath('en', '#projects')).toBe('/en#projects')
    expect(localePath('es', '#about')).toBe('/#about')
    expect(localePath('es')).toBe('/')
  })
})

describe('uiCopy', () => {
  it('defines exactly the same interface keys in both locales', () => {
    expect(shape(uiCopy('es'))).toEqual(shape(uiCopy('en')))
  })

  it('pluralizes counters', () => {
    expect(uiCopy('es').projects.showMore(1)).toBe('Ver 1 proyecto más')
    expect(uiCopy('en').projects.showMore(3)).toBe('Show 3 more projects')
    expect(uiCopy('en').projects.gallery.more(2)).toBe('+2')
  })
})
