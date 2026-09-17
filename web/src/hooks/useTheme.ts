import { useCallback, useState } from 'react'
import { THEME_STORAGE_KEY, nextTheme, resolveTheme, type Theme } from '@/lib/theme'

function documentTheme(): Theme {
  return resolveTheme(document.documentElement.getAttribute('data-theme'), false)
}

/** Reads the theme set by the pre-paint script and persists explicit changes. */
export function useTheme(): { theme: Theme; toggle: () => void } {
  const [theme, setTheme] = useState<Theme>(documentTheme)

  const toggle = useCallback(() => {
    const next = nextTheme(documentTheme())
    document.documentElement.setAttribute('data-theme', next)
    try {
      window.localStorage.setItem(THEME_STORAGE_KEY, next)
    } catch {
      // Storage can be unavailable (private mode); the choice then lasts for this page view.
    }
    setTheme(next)
  }, [])

  return { theme, toggle }
}
