export type Theme = 'light' | 'dark'

/** Also hard-coded in the pre-paint script of index.html; theme.test.ts keeps both in sync. */
export const THEME_STORAGE_KEY = 'portfolio-theme'

/** An explicit stored choice wins; otherwise the operating system preference decides. */
export function resolveTheme(stored: string | null, prefersDark: boolean): Theme {
  if (stored === 'light' || stored === 'dark') return stored
  return prefersDark ? 'dark' : 'light'
}

export function nextTheme(theme: Theme): Theme {
  return theme === 'dark' ? 'light' : 'dark'
}
