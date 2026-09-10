import {PRIMARY_DESTINATIONS} from '@/i18n/anchors';

/**
 * The ONE shared five-destination navigation definition (design spec §8, §19).
 *
 * Desktop header, mobile dialog, and the `<noscript>` fallback all render the
 * same `PRIMARY_DESTINATIONS` data. There is no second destination array in the
 * codebase. This is a Server Component: it renders plain anchors and never
 * attaches an event handler (the mobile dialog closes itself via event
 * delegation on a wrapping client element).
 */

export type PrimaryDestinationId =
  (typeof PRIMARY_DESTINATIONS)[number]['id'];

export type PrimaryDestinationLabels = Record<PrimaryDestinationId, string>;

type PrimaryNavigationProps = {
  /** Localized `Portfolio.nav.primaryLabel`. */
  navLabel: string;
  /** Localized label per stable destination id (`Portfolio.nav.*`). */
  labels: PrimaryDestinationLabels;
  /** `bar` = horizontal desktop header; `stack` = vertical dialog list. */
  variant?: 'bar' | 'stack';
  /** Optional stable id so a host can scope queries / focus fallbacks. */
  id?: string;
  className?: string;
};

export function PrimaryNavigation({
  navLabel,
  labels,
  variant = 'bar',
  id,
  className,
}: PrimaryNavigationProps) {
  const classes = ['primary-navigation', `primary-navigation--${variant}`];

  if (className) {
    classes.push(className);
  }

  return (
    <nav id={id} aria-label={navLabel} className={classes.join(' ')}>
      <ol className="primary-navigation__list">
        {PRIMARY_DESTINATIONS.map((destination, index) => (
          <li key={destination.id} className="primary-navigation__item">
            <a
              href={`#${destination.id}`}
              className="primary-navigation__link"
            >
              <span
                className="primary-navigation__index"
                aria-hidden="true"
              >
                {String(index + 1).padStart(2, '0')}
              </span>
              <span className="primary-navigation__label">
                {labels[destination.id]}
              </span>
            </a>
          </li>
        ))}
      </ol>
    </nav>
  );
}
