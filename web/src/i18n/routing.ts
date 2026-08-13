import {defineRouting} from 'next-intl/routing';

export const locales = ['es', 'en'] as const;

export type Locale = (typeof locales)[number];

export const defaultLocale: Locale = 'es';
export const localeCookieName = 'portfolio_locale';

export const routing = defineRouting({
  locales,
  defaultLocale,
  localePrefix: 'always',
  localeCookie: false,
});
