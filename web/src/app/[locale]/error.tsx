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
 *
 * Known, accepted trade-off: `layout.tsx` derives `headerVariant` from
 * `loadPublicPortfolio()` BEFORE `{children}` renders, so by the time this
 * boundary mounts (an exception from somewhere other than that already-
 * successful load) the header has already committed to `variant="valid"` and
 * its five primary-nav anchors (`#work #expertise #projects #approach
 * #contact`, plus the `#top` identity link) point at sections that do not
 * exist on this page. The App Router boundary model gives a leaf `error.tsx`
 * no way to reactively influence its ancestor layout's already-rendered
 * output without restructuring the whole shell, which is out of scope here.
 * The `<h1>` below is this component's own concession to that constraint: it
 * gives AT users landing on this boundary a real, correctly labelled heading
 * even though the header above it is not fully meaningful on this page.
 */

type LocalizedErrorProps = {
  error: Error & {digest?: string};
  reset: () => void;
};

export default function LocalizedError({reset}: LocalizedErrorProps) {
  const t = useTranslations('Portfolio.state');

  return (
    <div className="route-state route-state--error">
      <h1 className="route-state__title">{t('structuralFailure')}</h1>
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
