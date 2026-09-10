'use client';

import {useEffect, useState} from 'react';
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

  // Read the live URL hash on the client only. Starting empty keeps the first
  // client render identical to the server markup (no hydration mismatch); the
  // effect then syncs the real hash and tracks later `hashchange` events.
  const [currentHash, setCurrentHash] = useState('');

  useEffect(() => {
    const syncHash = () => setCurrentHash(window.location.hash);

    syncHash();
    window.addEventListener('hashchange', syncHash);

    return () => window.removeEventListener('hashchange', syncHash);
  }, []);

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
    <nav aria-label={label}>
      <ul className="flex gap-3">
        {languageOptions.map(({locale, labelKey}) => (
          <li key={locale}>
            <a
              href={localeHref(locale, currentHash)}
              aria-current={locale === currentLocale ? 'page' : undefined}
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
