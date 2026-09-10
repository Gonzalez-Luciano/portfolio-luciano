import {getLocale, getTranslations} from 'next-intl/server';

/**
 * Phase 5 localized 404 (design spec §17).
 *
 * Uses the visual shell (inherited from the localized layout) and the EXACT
 * `Portfolio.notFound.*` copy. The return action links to a valid localized
 * page. There is NO professional fallback content.
 */
export default async function NotFound() {
  const locale = await getLocale();
  const t = await getTranslations('Portfolio.notFound');

  return (
    <div className="route-state route-state--not-found">
      <h1 className="route-state__title">{t('title')}</h1>
      <p className="route-state__message">{t('explanation')}</p>
      <a className="route-state__action" href={`/${locale}`}>
        {t('returnAction')}
      </a>
    </div>
  );
}
