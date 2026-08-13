import type {ReactNode} from 'react';
import {hasLocale, NextIntlClientProvider} from 'next-intl';
import {setRequestLocale} from 'next-intl/server';
import {notFound} from 'next/navigation';
import '../globals.css';
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

  return (
    <html lang={locale} suppressHydrationWarning>
      <head>
        <script dangerouslySetInnerHTML={{__html: themeBootstrapSource}} />
      </head>
      <body className="flex min-h-screen flex-col">
        <NextIntlClientProvider messages={messages}>
          {children}
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
