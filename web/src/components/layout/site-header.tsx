import {LanguageSwitcher} from '@/components/language-switcher';
import {ThemeSwitcher} from '@/components/theme-switcher';
import {PRIMARY_DESTINATIONS, localeHref} from '@/i18n/anchors';
import type {Locale} from '@/i18n/routing';
import {MobileNavigation} from './mobile-navigation';
import {
  PrimaryNavigation,
  type PrimaryDestinationLabels,
} from './primary-navigation';

/**
 * Sticky top header (design spec §19).
 *
 * Server Component that hosts client islands (theme, language, native dialog).
 * Desktop vs mobile exposure is CSS-driven — both clusters render and CSS shows
 * the right one; the header never branches on a JS breakpoint. The Menu trigger
 * and interactive theme controls carry the JS-only dead-control hiding contract.
 *
 * `structural-failure` (spec §15.1): NO PrimaryNavigation, NO MobileNavigation,
 * NO `<noscript>` nav, NO identity link when the name is absent — but language
 * and theme controls stay.
 */

export type SiteHeaderLabels = {
  navLabel: string;
  destinations: PrimaryDestinationLabels;
  menu: {open: string; close: string; title: string};
  language: {label: string; spanish: string; english: string};
  theme: {label: string; current: string; light: string; dark: string};
};

type SiteHeaderProps = {
  locale: Locale;
  labels: SiteHeaderLabels;
  variant?: 'valid' | 'structural-failure';
  /** Profile-derived identity; Task 10 supplies it. */
  name?: string;
};

export function SiteHeader({
  locale,
  labels,
  variant = 'valid',
  name,
}: SiteHeaderProps) {
  const isValid = variant === 'valid';
  const otherLocale: Locale = locale === 'es' ? 'en' : 'es';
  const otherLocaleLabel =
    otherLocale === 'es' ? labels.language.spanish : labels.language.english;

  const languageSwitcher = (
    <LanguageSwitcher
      currentLocale={locale}
      label={labels.language.label}
      spanishLabel={labels.language.spanish}
      englishLabel={labels.language.english}
    />
  );

  const themeSwitcher = (
    <ThemeSwitcher
      label={labels.theme.label}
      currentThemeLabel={labels.theme.current}
      lightLabel={labels.theme.light}
      darkLabel={labels.theme.dark}
    />
  );

  return (
    <header className="site-header" data-variant={variant}>
      <div className="site-header__bar site-container">
        {name ? (
          <a
            id="site-header-home"
            className="site-header__identity"
            href="#top"
          >
            {name}
          </a>
        ) : null}

        {isValid ? (
          <div className="site-header__desktop header-desktop-only">
            <PrimaryNavigation
              id="primary-navigation-desktop"
              navLabel={labels.navLabel}
              labels={labels.destinations}
              variant="bar"
            />
            {languageSwitcher}
          </div>
        ) : null}

        <div className="site-header__theme">{themeSwitcher}</div>

        {isValid ? (
          <div className="site-header__mobile header-mobile-only">
            <MobileNavigation
              locale={locale}
              labels={{
                open: labels.menu.open,
                close: labels.menu.close,
                title: labels.menu.title,
                navLabel: labels.navLabel,
                destinations: labels.destinations,
                language: labels.language,
              }}
            />
          </div>
        ) : (
          <div className="site-header__failure">{languageSwitcher}</div>
        )}
      </div>

      {isValid ? (
        <noscript>
          <nav
            aria-label={labels.navLabel}
            className="site-header__noscript-nav"
          >
            <ul>
              {PRIMARY_DESTINATIONS.map((destination) => (
                <li key={destination.id}>
                  <a href={`#${destination.id}`}>
                    {labels.destinations[destination.id]}
                  </a>
                </li>
              ))}
              <li>
                <a href={localeHref(otherLocale)}>{otherLocaleLabel}</a>
              </li>
            </ul>
          </nav>
        </noscript>
      ) : null}
    </header>
  );
}
