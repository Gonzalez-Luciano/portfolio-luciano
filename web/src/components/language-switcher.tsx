'use client';

import {localeCookieName, type Locale} from '@/i18n/routing';

const oneYearInSeconds = 31_536_000;

type LanguageSwitcherProps = {
  currentLocale: Locale;
  label: string;
  spanishLabel: string;
  englishLabel: string;
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
}: LanguageSwitcherProps) {
  const labels = {spanishLabel, englishLabel};

  function persistExplicitLocale(locale: Locale) {
    // eslint-disable-next-line react-hooks/immutability -- this explicit click is the only locale-persistence boundary.
    document.cookie = `${localeCookieName}=${locale}; Path=/; Max-Age=${oneYearInSeconds}; SameSite=Lax`;
  }

  return (
    <nav aria-label={label}>
      <ul className="flex gap-3">
        {languageOptions.map(({locale, labelKey}) => (
          <li key={locale}>
            <a
              href={`/${locale}`}
              aria-current={locale === currentLocale ? 'page' : undefined}
              onClick={() => persistExplicitLocale(locale)}
            >
              {labels[labelKey]}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  );
}
