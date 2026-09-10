import type {Locale} from './routing';

/**
 * Single source of truth for the Phase 5 public-site navigation, hash, and
 * cross-locale fragment contract (design spec section 8).
 *
 * This module is intentionally pure data plus tiny string helpers. It contains
 * no section-name translation map, scroll-spy, IntersectionObserver, history
 * manipulation, or scroll-coordinate logic. `#top` and unknown hashes receive
 * no invented mapping.
 */

export type PrimaryDestination = {
  /** Stable, non-translated fragment id (rendered as `#work`, `#expertise`, …). */
  id: string;
  /** Lookup key into the `Portfolio.nav` message group for the localized label. */
  labelKey: string;
};

/**
 * The five primary navigation destinations, in the fixed spec order:
 * Work, Expertise, Projects, Approach, Contact. This order is identical on
 * desktop, mobile, and the no-JavaScript fallback.
 */
export const PRIMARY_DESTINATIONS: readonly PrimaryDestination[] = [
  {id: 'work', labelKey: 'nav.work'},
  {id: 'expertise', labelKey: 'nav.expertise'},
  {id: 'projects', labelKey: 'nav.projects'},
  {id: 'approach', labelKey: 'nav.approach'},
  {id: 'contact', labelKey: 'nav.contact'},
] as const;

/**
 * The eleven anchor ids that are preserved across a locale switch and managed
 * by {@link FragmentFocusManager}. `#top` is deliberately excluded: switching
 * locale from `#top` lands at the other locale's top without a fragment, and
 * `#top` navigation uses native browser positioning.
 */
export const PRESERVED_FRAGMENT_IDS: readonly string[] = [
  'work',
  'expertise',
  'projects',
  'approach',
  'contact',
  'about',
  'work-cases',
  'experience',
  'specialties',
  'technologies',
  'work-principles',
] as const;

function stripLeadingHash(hash: string): string {
  return hash.startsWith('#') ? hash.slice(1) : hash;
}

/**
 * True only for one of the eleven allowlisted ids. Accepts either a `#id` with
 * a leading hash or a bare `id`. Empty, `#`, `#top`, unknown, and missing
 * values all return false.
 */
export function isPreservedFragment(hash: string): boolean {
  if (!hash) {
    return false;
  }

  const id = stripLeadingHash(hash);

  return id.length > 0 && PRESERVED_FRAGMENT_IDS.includes(id);
}

/**
 * The real cross-locale href for a locale link.
 *
 * - no hash / empty / `#top` / unknown hash  -> `/{locale}`
 * - allowlisted hash                          -> `/{locale}#{id}`
 *
 * It never preserves a scroll coordinate and performs no navigation itself.
 */
export function localeHref(locale: Locale, hash?: string): string {
  if (hash && isPreservedFragment(hash)) {
    return `/${locale}#${stripLeadingHash(hash)}`;
  }

  return `/${locale}`;
}
