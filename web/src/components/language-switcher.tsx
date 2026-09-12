'use client';

import {localeHref} from '@/i18n/anchors';
import {localeCookieName, type Locale} from '@/i18n/routing';

const oneYearInSeconds = 31_536_000;

type LanguageSwitcherProps = {
  currentLocale: Locale;
  label: string;
  spanishLabel: string;
  englishLabel: string;
  /** Optional close hook used later by the mobile dialog to dismiss itself. */
  onNavigate?: () => void;
};

const languageOptions: ReadonlyArray<{
  locale: Locale;
  labelKey: 'spanishLabel' | 'englishLabel';
}> = [
  {locale: 'es', labelKey: 'spanishLabel'},
  {locale: 'en', labelKey: 'englishLabel'},
];

export function LanguageSwitcher({
  currentLocale,
  label,
  spanishLabel,
  englishLabel,
  onNavigate,
}: LanguageSwitcherProps) {
  const labels = {spanishLabel, englishLabel};

  function persistExplicitLocale(locale: Locale) {
    // eslint-disable-next-line react-hooks/immutability -- this explicit click is the only locale-persistence boundary.
    document.cookie = `${localeCookieName}=${locale}; Path=/; Max-Age=${oneYearInSeconds}; SameSite=Lax`;
  }

  function handleActivate(locale: Locale) {
    persistExplicitLocale(locale);
    onNavigate?.();
    // Navigation proceeds normally via the anchor href so Server Components
    // reacquire the target locale from Laravel. No preventDefault, no scroll.
  }

  return (
    <nav aria-label={label} className="language-switcher">
      <ul className="language-switcher__list">
        {languageOptions.map(({locale, labelKey}) => (
          <li key={locale} className="language-switcher__item">
            <a
              href={localeHref(locale)}
              aria-current={locale === currentLocale ? 'page' : undefined}
              className="language-switcher__link"
              onClick={() => handleActivate(locale)}
            >
              {labels[labelKey]}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  );
}
