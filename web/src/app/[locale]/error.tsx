'use client';

import {useTranslations} from 'next-intl';

/**
 * App Router safety net for an UNEXPECTED exception in the localized subtree
 * below the localized layout (design spec §17).
 *
 * It is a Client Component by framework requirement. It renders localized safe
 * copy, exposes NO technical detail (no message, digest, or stack), and offers
 * the boundary's `reset()` recovery. It is NOT the normal endpoint-failure
 * model — expected API failures are typed values handled by page composition.
 * Because it renders under the localized layout, the next-intl provider is
 * available.
 */

type LocalizedErrorProps = {
  error: Error & {digest?: string};
  reset: () => void;
};

export default function LocalizedError({reset}: LocalizedErrorProps) {
  const t = useTranslations('Portfolio.state');

  return (
    <div className="route-state route-state--error">
      <p className="route-state__message">{t('structuralFailure')}</p>
      <button
        type="button"
        className="route-state__action"
        onClick={() => reset()}
      >
        {t('retry')}
      </button>
    </div>
  );
}
