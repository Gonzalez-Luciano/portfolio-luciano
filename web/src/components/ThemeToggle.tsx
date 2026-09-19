import { Moon, Sun } from 'lucide-react'
import type { UiCopy } from '@/content'
import type { Theme } from '@/lib/theme'

export function ThemeToggle({ ui, theme, onToggle }: { ui: UiCopy; theme: Theme; onToggle: () => void }) {
  const label = theme === 'dark' ? ui.theme.toLight : ui.theme.toDark

  return (
    <button
      type="button"
      onClick={onToggle}
      aria-label={label}
      title={label}
      className="flex h-11 w-11 items-center justify-center rounded-full transition-opacity hover:opacity-70"
    >
      {theme === 'dark' ? <Sun size={18} aria-hidden="true" /> : <Moon size={18} aria-hidden="true" />}
    </button>
  )
}
