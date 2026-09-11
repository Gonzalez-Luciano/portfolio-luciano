import {cache} from 'react';
import type {Locale} from '@/i18n/routing';
import {
  fetchExperiences,
  fetchProfile,
  fetchProjects,
  fetchSite,
  fetchTechnologies,
  fetchWorkCases,
} from './fetchers';
import type {PublicPortfolioResults} from './types';

/**
 * Server-only coordinated acquisition for the Phase 5 public portfolio site
 * (design spec section 13).
 *
 * This module only *coordinates* the six independent Phase 4 reads. It holds
 * no presentation policy: criticality (structural vs. regional failure), empty
 * states, ordering, and grouping all belong to the page and its sections.
 *
 * It adds no persistent or cross-request cache of any kind, no Next Data Cache
 * opt-in, no ad-hoc module-level result store, and no bespoke sharing helper.
 * The only request-scoped sharing is React `cache()` on the exported loader.
 * Every underlying `fetch` is already `no-store` in Task 2, so the next
 * request performs six fresh reads against Laravel.
 */

/**
 * The injection seam for the six Task 2 fetchers. Test-only in intent:
 * production callers use {@link defaultFetchers}. Each entry keeps the exact
 * Task 2 signature so the loader forwards nothing but the locale.
 */
export type PublicPortfolioFetchers = {
  fetchProfile: typeof fetchProfile;
  fetchSite: typeof fetchSite;
  fetchExperiences: typeof fetchExperiences;
  fetchWorkCases: typeof fetchWorkCases;
  fetchProjects: typeof fetchProjects;
  fetchTechnologies: typeof fetchTechnologies;
};

const defaultFetchers: PublicPortfolioFetchers = {
  fetchProfile,
  fetchSite,
  fetchExperiences,
  fetchWorkCases,
  fetchProjects,
  fetchTechnologies,
};

/**
 * Start all six independent endpoint reads together and preserve each result.
 *
 * The Task 2 fetchers never reject for a known operational failure — they
 * resolve to `{ok: false, failure}` — so a single `Promise.all` over the six
 * non-rejecting promises is the correct, simplest primitive: one failed
 * endpoint neither rejects the batch nor discards the other five healthy
 * results. There is no sequential `await`; the six calls are issued before the
 * first suspension point.
 */
export async function loadPublicPortfolioUncached(
  locale: Locale,
  dependencies: PublicPortfolioFetchers = defaultFetchers,
): Promise<PublicPortfolioResults> {
  const [profile, site, experiences, workCases, projects, technologies] =
    await Promise.all([
      dependencies.fetchProfile(locale),
      dependencies.fetchSite(locale),
      dependencies.fetchExperiences(locale),
      dependencies.fetchWorkCases(locale),
      dependencies.fetchProjects(locale),
      dependencies.fetchTechnologies(locale),
    ]);

  return {
    profile,
    site,
    experiences,
    workCases,
    projects,
    technologies,
  };
}

/**
 * The production loader. React `cache()` shares one coordinated acquisition
 * between `generateMetadata` and the page within a single request, and a later
 * request gets six fresh no-store reads. Nothing persistent.
 */
export const loadPublicPortfolio = cache(loadPublicPortfolioUncached);
