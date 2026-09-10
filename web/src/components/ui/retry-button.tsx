'use client';

import {useTransition} from 'react';
import {useRouter} from 'next/navigation';

type RetryButtonProps = {
  label: string;
  /** Shown while the refresh transition is pending, when supplied. */
  pendingLabel?: string;
};

/**
 * The only Retry control for expected structural and regional failure surfaces
 * (design spec section 18).
 *
 * It knows nothing but its localized labels. Activation calls
 * `router.refresh()`, which reacquires all six no-store endpoints on the server
 * while preserving locale, pathname, query, and fragment. There is no
 * resource-specific client fetch, timer, polling, auto-retry, backoff, or retry
 * state machine. The pending flag is only the transition primitive React
 * already exposes.
 */
export function RetryButton({label, pendingLabel}: RetryButtonProps) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const showPending = isPending && pendingLabel !== undefined;

  return (
    <button
      type="button"
      disabled={showPending}
      onClick={() => {
        startTransition(() => {
          router.refresh();
        });
      }}
    >
      {showPending ? pendingLabel : label}
    </button>
  );
}
