import type {SiteConfiguration} from '@/lib/api/types';
import {NeutralEmptyState} from '@/components/ui/content-state';

/**
 * Working approach — `#approach` (design spec §22, §16, §20, §8).
 *
 * Server Component. It receives the already-validated `site.work_principles`
 * array (which may be `[]`) and never fetches. `<section id="approach">`
 * ALWAYS renders on a structurally valid page, with a VISIBLE
 * `<h2 id="approach-heading">`.
 *
 *   principles non-empty -> an ordered list carrying the `#work-principles`
 *                           granular anchor, rendering each `principle.statement`
 *                           in API order. The list is genuinely a sequence, so
 *                           `<ol>` carries that meaning; the numeric marker is
 *                           supporting hierarchy only (Phase 2 "Approach" beats).
 *   principles []        -> `<NeutralEmptyState>` under `#approach`, and
 *                           `#work-principles` is OMITTED (no anchor).
 */

const APPROACH_HEADING_ID = 'approach-heading';

export type ApproachSectionLabels = {
  /** `Portfolio.sections.approach` — the visible section heading. */
  sectionTitle: string;
  /** `Portfolio.state.neutralEmpty`. */
  neutralEmpty: string;
};

type ApproachSectionProps = {
  principles: SiteConfiguration['work_principles'];
  labels: ApproachSectionLabels;
};

export function ApproachSection({principles, labels}: ApproachSectionProps) {
  return (
    <section
      id="approach"
      tabIndex={-1}
      aria-labelledby={APPROACH_HEADING_ID}
      className="approach"
    >
      <h2 id={APPROACH_HEADING_ID} className="approach__heading">
        {labels.sectionTitle}
      </h2>
      {principles.length > 0 ? (
        <ol id="work-principles" tabIndex={-1} className="principles">
          {principles.map((principle) => (
            <li key={principle.key} className="principles__item">
              {principle.statement}
            </li>
          ))}
        </ol>
      ) : (
        <NeutralEmptyState message={labels.neutralEmpty} />
      )}
    </section>
  );
}
