import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { THEME_STORAGE_KEY, nextTheme, resolveTheme } from '@/lib/theme'

describe('resolveTheme', () => {
  it('uses a valid stored choice over the system preference', () => {
    expect(resolveTheme('dark', false)).toBe('dark')
    expect(resolveTheme('light', true)).toBe('light')
  })

  it('falls back to the system preference for a missing or invalid choice', () => {
    expect(resolveTheme(null, true)).toBe('dark')
    expect(resolveTheme(null, false)).toBe('light')
    expect(resolveTheme('sepia', true)).toBe('dark')
  })

  it('toggles between the two themes', () => {
    expect(nextTheme('light')).toBe('dark')
    expect(nextTheme('dark')).toBe('light')
  })
})

describe('index.html pre-paint script', () => {
  const html = readFileSync(new URL('../../index.html', import.meta.url), 'utf8')

  it('sets data-theme with the same storage key before the app loads', () => {
    expect(html).toContain(`'${THEME_STORAGE_KEY}'`)
    expect(html).toContain("setAttribute('data-theme'")
    expect(html.indexOf('data-theme')).toBeLessThan(html.indexOf('/src/main.tsx'))
  })

  it('requests no external font stylesheet', () => {
    expect(html).not.toMatch(/<link[^>]+stylesheet/)
  })
})
