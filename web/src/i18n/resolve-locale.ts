import {defaultLocale, locales, type Locale} from './routing';

function toLocale(value: string | undefined): Locale | undefined {
  const normalized = value?.trim().toLowerCase();

  return locales.find((locale) => locale === normalized);
}

type LanguagePreference = {
  locale: Locale;
  quality: number;
  position: number;
};

function parseAcceptLanguage(
  acceptLanguage: string | null,
): Locale | undefined {
  if (!acceptLanguage) {
    return undefined;
  }

  const preferences = acceptLanguage
    .split(',')
    .map((entry, position): LanguagePreference | undefined => {
      const [languageRange, ...parameters] = entry.trim().split(';');
      const locale = toLocale(languageRange?.split('-')[0]);
      const qualityParameter = parameters.find((parameter) =>
        parameter.trim().startsWith('q='),
      );
      const qualityValue = qualityParameter?.trim().slice(2);
      const quality = qualityValue === undefined ? 1 : Number(qualityValue);

      if (!locale || !Number.isFinite(quality) || quality <= 0) {
        return undefined;
      }

      return {locale, quality, position};
    })
    .filter(
      (preference): preference is LanguagePreference =>
        preference !== undefined,
    )
    .sort(
      (left, right) =>
        right.quality - left.quality || left.position - right.position,
    );

  return preferences[0]?.locale;
}

export function resolveLocale(
  cookieValue: string | undefined,
  acceptLanguage: string | null,
): Locale {
  return (
    toLocale(cookieValue) ??
    parseAcceptLanguage(acceptLanguage) ??
    defaultLocale
  );
}
