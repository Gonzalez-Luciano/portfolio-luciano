import {hasLocale} from 'next-intl';
import {getTranslations, setRequestLocale} from 'next-intl/server';
import {notFound} from 'next/navigation';
import {LanguageSwitcher} from '@/components/language-switcher';
import {locales, routing} from '@/i18n/routing';

type LocalePageProps = {
  params: Promise<{locale: string}>;
};

export function generateStaticParams() {
  return locales.map((locale) => ({locale}));
}

export default async function LocalePage({params}: LocalePageProps) {
  const {locale} = await params;

  if (!hasLocale(routing.locales, locale)) {
    notFound();
  }

  setRequestLocale(locale);
  const t = await getTranslations({locale, namespace: 'Foundation'});

  return (
    <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-6 py-16">
      <header>
        <h1 className="text-3xl font-semibold tracking-tight">{t('title')}</h1>
        <p>{t('currentLocale', {locale})}</p>
      </header>
      <LanguageSwitcher
        currentLocale={locale}
        label={t('languageSwitcherLabel')}
        spanishLabel={t('spanish')}
        englishLabel={t('english')}
      />
      <p>{t('themeSlot')}</p>
      <p>{t('apiStatusSlot')}</p>
    </main>
  );
}
