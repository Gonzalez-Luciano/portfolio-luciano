import {RetryButton} from './retry-button';

/**
 * Server-rendered state surfaces for the Phase 5 public site (design spec
 * sections 15 and 16).
 *
 * None of these emit `role="alert"`, `role="status"`, or `aria-live`
 * automatically: an error present in the initial SSR HTML is an identifiable
 * semantic region with heading/text and an accessible button, not an urgent
 * live announcement. The localized strings are always passed in by the caller
 * (the page / sections forward the `Portfolio` catalog values).
 */

type RetryCopy = {
  label: string;
  pendingLabel?: string;
};

type FailureProps = {
  message: string;
  retry: RetryCopy;
};

type NeutralEmptyStateProps = {
  message: string;
};

/**
 * Whole-page failure: Profile or Site could not be loaded. Keeps a semantic
 * region, the structural-failure text, and a {@link RetryButton}.
 */
export function StructuralFailure({message, retry}: FailureProps) {
  return (
    <section className="content-state content-state--structural">
      <h2>{message}</h2>
      <RetryButton label={retry.label} pendingLabel={retry.pendingLabel} />
    </section>
  );
}

/**
 * One region failed while the rest of a structurally valid page renders.
 *
 * It always sits inside a host `<section>` that already provides the granular
 * anchor and its `<h3>` heading (WorkGroup, ExpertiseGroup, …), so this surface
 * renders the safe copy as a plain paragraph — never a competing heading — plus
 * the {@link RetryButton}. It keeps its identifiable region wrapper and the
 * no-automatic-live-region contract (spec §15.2 / §20).
 */
export function RegionalFailure({message, retry}: FailureProps) {
  return (
    <section className="content-state content-state--regional">
      <p className="content-state__message">{message}</p>
      <RetryButton label={retry.label} pendingLabel={retry.pendingLabel} />
    </section>
  );
}

/**
 * A successful-but-empty group. Ordinary content: no Retry control and no
 * live-region role.
 */
export function NeutralEmptyState({message}: NeutralEmptyStateProps) {
  return (
    <section className="content-state content-state--neutral">
      <p>{message}</p>
    </section>
  );
}
