import type {ReactNode} from 'react';
import {hasLocale, NextIntlClientProvider} from 'next-intl';
import {setRequestLocale} from 'next-intl/server';
import {notFound} from 'next/navigation';
import '../globals.css';
import {SiteFooter} from '@/components/layout/site-footer';
import {SiteHeader, type SiteHeaderLabels} from '@/components/layout/site-header';
import {FragmentFocusManager} from '@/components/ui/fragment-focus-manager';
import {themeBootstrapSource} from '@/theme/bootstrap';
import {locales, routing} from '@/i18n/routing';

type LocaleLayoutProps = Readonly<{
  children: ReactNode;
  params: Promise<{locale: string}>;
}>;

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
          <SiteHeader locale={locale} labels={headerLabels} />
          <main id="main-content" tabIndex={-1} className="site-main">
            {children}
          </main>
          <SiteFooter />
          <FragmentFocusManager />
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
