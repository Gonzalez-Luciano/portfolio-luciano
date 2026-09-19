import { alternateLocale, localePath, type Locale, type UiCopy } from '@/content'

const LOCALES: readonly Locale[] = ['es', 'en']

/** "ES / EN": the current language is boxed; the other one opens the same section in that language. */
export function LanguageSwitch({ ui, locale }: { ui: UiCopy; locale: Locale }) {
  const target = alternateLocale(locale)

  return (
    <span className="flex items-center font-mono text-xs font-medium">
      {LOCALES.map((option, index) => (
        <span key={option} className="flex items-center">
          {index > 0 && (
            <span aria-hidden="true" className="px-1 opacity-60">
              /
            </span>
          )}
          {option === locale ? (
            <span aria-current="true" lang={option} className="flex min-h-11 items-center">
              <span className="rounded-sm border border-current px-1.5 py-0.5">{option.toUpperCase()}</span>
            </span>
          ) : (
            <a
              href={localePath(target)}
              hrefLang={target}
              lang={target}
              // Label-in-name (WCAG 2.5.3): the visible text ("EN"/"ES") stays inside the accessible name.
              aria-label={`${option.toUpperCase()} · ${ui.language.switchTo}`}
              // The anchor is read at click time so the other language opens on the same section.
              onClick={(event) => {
                event.currentTarget.href = localePath(target, window.location.hash)
              }}
              className="flex min-h-11 min-w-11 items-center justify-center px-1.5 transition-opacity hover:opacity-70"
            >
              {option.toUpperCase()}
            </a>
          )}
        </span>
      ))}
    </span>
  )
}
