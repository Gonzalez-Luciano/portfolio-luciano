import { Menu, X } from 'lucide-react'
import { useEffect, useRef, useState, type MouseEvent } from 'react'
import { LanguageSwitch } from '@/components/LanguageSwitch'
import { ThemeToggle } from '@/components/ThemeToggle'
import type { Locale, UiCopy } from '@/content'
import type { CvLink, Photo } from '@/lib/api'
import { sectionNumber, type SectionId } from '@/lib/sections'
import type { Theme } from '@/lib/theme'

type Props = {
  ui: UiCopy
  locale: Locale
  name: string | null
  avatar: Photo | null
  cv: CvLink | null
  sections: SectionId[]
  activeSection: SectionId | null
  /** True while the scroll scene fills the viewport: transparent bar tinted by the frame. */
  overScene: boolean
  lightText: boolean
  veilOpacity: number
  theme: Theme
  onToggleTheme: () => void
}

export function SiteNav({ ui, locale, name, avatar, cv, sections, activeSection, overScene, lightText, veilOpacity, theme, onToggleTheme }: Props) {
  const dialogRef = useRef<HTMLDialogElement>(null)
  const menuButtonRef = useRef<HTMLButtonElement>(null)
  const [menuOpen, setMenuOpen] = useState(false)

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (menuOpen && !dialog.open) dialog.showModal()
    if (!menuOpen && dialog.open) dialog.close()
    document.body.style.overflow = menuOpen ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [menuOpen])

  useEffect(() => {
    const desktop = window.matchMedia('(min-width: 1024px)')
    const onChange = () => {
      if (desktop.matches) setMenuOpen(false)
    }
    desktop.addEventListener('change', onChange)
    return () => desktop.removeEventListener('change', onChange)
  }, [])

  // Close the modal before scrolling: a modal dialog and the scroll lock would otherwise swallow the jump.
  const navigateFromMenu = (event: MouseEvent<HTMLAnchorElement>, id: SectionId) => {
    event.preventDefault()
    dialogRef.current?.close()
    document.body.style.overflow = ''
    document.getElementById(id)?.scrollIntoView()
    window.history.pushState(null, '', `#${id}`)
    // pushState does not fire `hashchange` on its own; dispatch it so anything reading the current
    // hash at render time (e.g. LanguageSwitch, to keep the other language on the same section) stays in sync.
    window.dispatchEvent(new Event('hashchange'))
  }

  const tone = overScene ? (lightText ? 'text-scene-light' : 'text-scene-ink') : 'text-ink'
  const secondaryControls = (
    <>
      <LanguageSwitch ui={ui} locale={locale} />
      <ThemeToggle ui={ui} theme={theme} onToggle={onToggleTheme} />
      {cv && (
        <a
          href={cv.url}
          // Label-in-name (WCAG 2.5.3): the visible text ("CV") stays inside the accessible name.
          aria-label={`${ui.cvLabel} — ${cv.label}`}
          className="flex min-h-11 items-center px-1 transition-opacity hover:opacity-70"
        >
          <span className="rounded-sm border border-current px-2 py-0.5 font-mono text-xs font-medium">{ui.cvLabel}</span>
        </a>
      )}
    </>
  )

  return (
    <header className={`fixed inset-x-0 top-0 z-50 transition-colors duration-300 ${overScene ? 'bg-transparent' : 'border-b border-line bg-canvas'}`}>
      <a
        href="#content"
        className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-[60] focus:rounded focus:bg-canvas focus:px-4 focus:py-2 focus:text-ink"
      >
        {ui.skipToContent}
      </a>

      {overScene && (
        <div aria-hidden="true" className="pointer-events-none absolute inset-0 bg-scene-veil transition-opacity duration-150" style={{ opacity: veilOpacity }} />
      )}

      <nav aria-label={ui.navLabel} className={`relative mx-auto flex h-16 max-w-content items-center justify-between gap-6 px-6 transition-colors duration-500 sm:px-10 lg:px-16 ${tone}`}>
        <a href="#top" className="flex min-h-11 items-center gap-3">
          {avatar && (
            <img
              src={avatar.url}
              alt=""
              width={28}
              height={28}
              decoding="async"
              className={`h-7 rounded-full object-cover transition-[opacity,width] duration-300 ${overScene ? 'w-0 opacity-0' : 'w-7 opacity-100'}`}
            />
          )}
          <span className="font-display text-lg font-light">{name ?? ui.backToTop}</span>
        </a>

        <ul className="hidden items-center gap-8 lg:flex">
          {sections.map((id) => {
            const active = !overScene && activeSection === id
            return (
              <li key={id}>
                <a href={`#${id}`} aria-current={active ? 'location' : undefined} className="relative flex min-h-11 items-center gap-2 text-sm font-semibold">
                  {active && <span className="label text-accent">{sectionNumber(id)}</span>}
                  {ui.sections[id].nav}
                  {active && <span aria-hidden="true" className="absolute inset-x-0 bottom-2 h-0.5 bg-accent" />}
                </a>
              </li>
            )
          })}
        </ul>

        <div className="hidden items-center gap-2 lg:flex">{secondaryControls}</div>

        <button
          ref={menuButtonRef}
          type="button"
          onClick={() => setMenuOpen(true)}
          aria-haspopup="dialog"
          aria-expanded={menuOpen}
          className="flex min-h-11 items-center gap-2 text-sm font-semibold lg:hidden"
        >
          <Menu size={18} aria-hidden="true" />
          {ui.menu.open}
        </button>
      </nav>

      <dialog
        ref={dialogRef}
        aria-label={ui.menu.title}
        onClose={() => {
          setMenuOpen(false)
          menuButtonRef.current?.focus()
        }}
        className="m-0 h-full max-h-none w-full max-w-none bg-canvas p-0 text-ink backdrop:bg-scene-veil/60"
      >
        <div className="flex h-full flex-col overflow-y-auto px-6 pb-10 pt-4 sm:px-10">
          <div className="flex h-12 items-center justify-between">
            <span className="label text-muted">{ui.menu.title}</span>
            <button
              type="button"
              onClick={() => setMenuOpen(false)}
              aria-label={ui.menu.close}
              className="flex h-11 w-11 items-center justify-center rounded-full border border-line"
            >
              <X size={18} aria-hidden="true" />
            </button>
          </div>

          <ol className="flex flex-1 flex-col justify-center gap-2">
            {sections.map((id) => (
              <li key={id}>
                <a href={`#${id}`} onClick={(event) => navigateFromMenu(event, id)} className="flex min-h-11 items-baseline gap-4 py-2">
                  <span className="label text-accent">{sectionNumber(id)}</span>
                  <span className="font-display text-3xl font-light">{ui.sections[id].nav}</span>
                </a>
              </li>
            ))}
          </ol>

          <div className="flex flex-wrap items-center gap-3 border-t border-line pt-6">{secondaryControls}</div>
        </div>
      </dialog>
    </header>
  )
}
