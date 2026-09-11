import type {ReactNode} from 'react';
import {hasLocale, NextIntlClientProvider} from 'next-intl';
import {setRequestLocale} from 'next-intl/server';
import {notFound} from 'next/navigation';
import '../globals.css';
import {SiteFooter} from '@/components/layout/site-footer';
import {
  SiteHeader,
  type SiteHeaderLabels,
} from '@/components/layout/site-header';
import {FragmentFocusManager} from '@/components/ui/fragment-focus-manager';
import {loadPublicPortfolio} from '@/lib/api/load-public-portfolio';
import {themeBootstrapSource} from '@/theme/bootstrap';
import {locales, routing} from '@/i18n/routing';

type LocaleLayoutProps = Readonly<{
  children: ReactNode;
  params: Promise<{locale: string}>;
}>;

// Spec §14: the localized route renders dynamically at request time; the build
// must succeed while Laravel is unavailable and must not run the loader.
export const dynamic = 'force-dynamic';

export function generateStaticParams() {
  return locales.map((locale) => ({locale}));
}

export default async function LocaleLayout({
  children,
  params,
}: LocaleLayoutProps) {
  const {locale} = await params;

  if (!hasLocale(routing.locales, locale)) {
    notFound();
  }

  setRequestLocale(locale);
  const messages = (await import(`../../../messages/${locale}.json`)).default;
  const portfolio = messages.Portfolio;

  // The layout is a THIRD consumer of the shared request-scoped loader
  // (alongside the page and generateMetadata). React `cache()` dedupes within a
  // single request, so this adds no Laravel read; Task 13 verifies the count.
  // It drives only the header/footer variant — the page still owns criticality.
  const results = await loadPublicPortfolio(locale);
  const profileName = results.profile.ok
    ? results.profile.data.name
    : undefined;
  const headerVariant =
    results.profile.ok && results.site.ok ? 'valid' : 'structural-failure';
  const footerLinks = results.site.ok
    ? results.site.data.professional_links
    : undefined;

  const headerLabels: SiteHeaderLabels = {
    navLabel: portfolio.nav.primaryLabel,
    destinations: {
      work: portfolio.nav.work,
      expertise: portfolio.nav.expertise,
      projects: portfolio.nav.projects,
      approach: portfolio.nav.approach,
      contact: portfolio.nav.contact,
    },
    menu: {
      open: portfolio.menu.open,
      close: portfolio.menu.close,
      title: portfolio.menu.title,
    },
    language: {
      label: portfolio.language.label,
      spanish: portfolio.language.spanish,
      english: portfolio.language.english,
    },
    theme: {
      label: portfolio.theme.label,
      current: portfolio.theme.current,
      light: portfolio.theme.light,
      dark: portfolio.theme.dark,
    },
  };

  return (
    <html lang={locale} suppressHydrationWarning>
      <head>
        <script dangerouslySetInnerHTML={{__html: themeBootstrapSource}} />
      </head>
      <body className="flex min-h-screen flex-col">
        <NextIntlClientProvider messages={messages}>
          <a className="skip-link" href="#main-content">
            {portfolio.skipToContent}
          </a>
          <SiteHeader
            locale={locale}
            labels={headerLabels}
            variant={headerVariant}
            name={profileName}
          />
          <main id="main-content" tabIndex={-1} className="site-main">
            {children}
          </main>
          <SiteFooter name={profileName} links={footerLinks} />
          <FragmentFocusManager />
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
