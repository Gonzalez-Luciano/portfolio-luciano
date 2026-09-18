import type { Locale } from '@/lib/api'

const MONTHS: Record<Locale, readonly string[]> = {
  es: ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sept', 'oct', 'nov', 'dic'],
  en: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
}

const PRESENT: Record<Locale, string> = { es: 'actualidad', en: 'present' }

/** `YYYY-MM` from the API to a short localized month and year. */
export function formatMonth(value: string, locale: Locale): string {
  const [year, month] = value.split('-')
  return `${MONTHS[locale][Number(month) - 1]} ${year}`
}

export function formatPeriod(start: string, end: string | null, locale: Locale): string {
  return `${formatMonth(start, locale)} – ${end === null ? PRESENT[locale] : formatMonth(end, locale)}`
}

export function formatYears(start: number | null, end: number | null): string | null {
  if (start === null && end === null) return null
  if (start === null || end === null || start === end) return String(start ?? end)
  return `${start} – ${end}`
}

export function emailAddress(href: string): string {
  return href.replace(/^mailto:/, '')
}

/**
 * Drops `null`, `undefined` and empty-string values from a list, preserving
 * the order of what remains. Used to join optional interface fragments (e.g.
 * location + work mode) without repeating a `.filter(Boolean)` type guard.
 */
export function compact<T>(values: ReadonlyArray<T | null | undefined | ''>): T[] {
  return values.filter((value): value is T => value !== null && value !== undefined && value !== '')
}
