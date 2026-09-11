import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';
import {beforeEach, describe, expect, it, vi} from 'vitest';
import type {Locale} from '@/i18n/routing';
import type {PublicRequestTestOptions} from './client';
import {
  loadPublicPortfolio,
  loadPublicPortfolioUncached,
  type PublicPortfolioFetchers,
} from './load-public-portfolio';
import type {EndpointResult} from './types';
import {
  validExperience,
  validProfile,
  validProject,
  validSite,
  validTechnology,
  validWorkCase,
} from '@/test/fixtures/public-api';

/**
 * A promise whose settlement is driven by the test, not by the code under test.
 * It lets us observe the loader *between* "all fetchers started" and "any
 * fetcher resolved", which is where the parallel-vs-sequential distinction
 * lives.
 */
type Deferred<T> = {
  promise: Promise<T>;
  resolve: (value: T) => void;
};

function deferred<T>(): Deferred<T> {
  let resolve!: (value: T) => void;
  const promise = new Promise<T>((res) => {
    resolve = res;
  });
  return {promise, resolve};
}

type FetcherMock<T> = ReturnType<
  typeof vi.fn<
    (
      locale: Locale,
      options?: PublicRequestTestOptions,
    ) => Promise<EndpointResult<T>>
  >
>;

/**
 * The `react` module is mocked so this file can prove the *wiring* of the
 * production export: that `loadPublicPortfolio` is exactly `cache(
 * loadPublicPortfolioUncached)`. `importOriginal` passes through every other
 * React export untouched, and Vitest scopes `vi.mock` to this single test
 * file, so no other suite sees the spy. `cacheSpy` returns a fresh wrapper
 * function (not its argument) so the assertions can distinguish "wrapped by
 * cache()" from "re-exported as-is".
 *
 * These are WIRING assertions only. Task 13 owns the proof of the real Next
 * request lifecycle: same-request sharing between `generateMetadata` and the
 * page, plus six fresh no-store reacquisitions on the next request.
 */
const {cacheSpy} = vi.hoisted(() => {
  const spy = vi.fn((fn: (...args: unknown[]) => unknown) => {
    const wrapped = (...args: unknown[]) => fn(...args);
    return wrapped;
  });
  return {cacheSpy: spy};
});

vi.mock('react', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react')>();
  return {
    ...actual,
    cache: cacheSpy,
  };
});

type Deferreds = {
  profile: Deferred<EndpointResult<typeof validProfile>>;
  site: Deferred<EndpointResult<typeof validSite>>;
  experiences: Deferred<EndpointResult<(typeof validExperience)[]>>;
  workCases: Deferred<EndpointResult<(typeof validWorkCase)[]>>;
  projects: Deferred<EndpointResult<(typeof validProject)[]>>;
  technologies: Deferred<EndpointResult<(typeof validTechnology)[]>>;
};

type Fetchers = {
  fetchProfile: FetcherMock<typeof validProfile>;
  fetchSite: FetcherMock<typeof validSite>;
  fetchExperiences: FetcherMock<(typeof validExperience)[]>;
  fetchWorkCases: FetcherMock<(typeof validWorkCase)[]>;
  fetchProjects: FetcherMock<(typeof validProject)[]>;
  fetchTechnologies: FetcherMock<(typeof validTechnology)[]>;
};

function buildFetchers(): {deferreds: Deferreds; fetchers: Fetchers} {
  const deferreds: Deferreds = {
    profile: deferred(),
    site: deferred(),
    experiences: deferred(),
    workCases: deferred(),
    projects: deferred(),
    technologies: deferred(),
  };

  const fetchers: Fetchers = {
    fetchProfile: vi.fn(() => deferreds.profile.promise),
    fetchSite: vi.fn(() => deferreds.site.promise),
    fetchExperiences: vi.fn(() => deferreds.experiences.promise),
    fetchWorkCases: vi.fn(() => deferreds.workCases.promise),
    fetchProjects: vi.fn(() => deferreds.projects.promise),
    fetchTechnologies: vi.fn(() => deferreds.technologies.promise),
  };

  return {deferreds, fetchers};
}

describe('loadPublicPortfolioUncached', () => {
  it('starts all six fetchers before any of them settles', () => {
    const {fetchers} = buildFetchers();

    // Call WITHOUT awaiting: no deferred has been resolved yet.
    void loadPublicPortfolioUncached(
      'es',
      fetchers as unknown as PublicPortfolioFetchers,
    );

    for (const fetcher of Object.values(fetchers)) {
      expect(fetcher).toHaveBeenCalledTimes(1);
    }
  });

  it('passes the locale through to every fetcher', () => {
    const {fetchers} = buildFetchers();

    void loadPublicPortfolioUncached(
      'en',
      fetchers as unknown as PublicPortfolioFetchers,
    );

    for (const fetcher of Object.values(fetchers)) {
      expect(fetcher).toHaveBeenCalledWith('en');
    }
  });

  it('preserves every healthy result while one endpoint fails independently', async () => {
    const {deferreds, fetchers} = buildFetchers();

    const loading = loadPublicPortfolioUncached(
      'es',
      fetchers as unknown as PublicPortfolioFetchers,
    );

    deferreds.profile.resolve({ok: true, data: validProfile});
    deferreds.site.resolve({ok: true, data: validSite});
    deferreds.experiences.resolve({ok: true, data: [validExperience]});
    deferreds.workCases.resolve({ok: true, data: [validWorkCase]});
    deferreds.technologies.resolve({ok: true, data: [validTechnology]});
    deferreds.projects.resolve({
      ok: false,
      failure: {endpoint: 'projects', kind: 'http', status: 503},
    });

    const result = await loading;

    expect(result).toEqual({
      profile: {ok: true, data: validProfile},
      site: {ok: true, data: validSite},
      experiences: {ok: true, data: [validExperience]},
      workCases: {ok: true, data: [validWorkCase]},
      projects: {
        ok: false,
        failure: {endpoint: 'projects', kind: 'http', status: 503},
      },
      technologies: {ok: true, data: [validTechnology]},
    });
  });

  it('maps the fetcher results onto their own keys, not by tuple position', async () => {
    const {deferreds, fetchers} = buildFetchers();

    const loading = loadPublicPortfolioUncached(
      'es',
      fetchers as unknown as PublicPortfolioFetchers,
    );

    // Resolve in a deliberately shuffled order to catch a positional mixup.
    deferreds.technologies.resolve({ok: true, data: [validTechnology]});
    deferreds.workCases.resolve({ok: true, data: [validWorkCase]});
    deferreds.experiences.resolve({
      ok: false,
      failure: {endpoint: 'experiences', kind: 'network'},
    });
    deferreds.site.resolve({ok: true, data: validSite});
    deferreds.projects.resolve({ok: true, data: [validProject]});
    deferreds.profile.resolve({ok: true, data: validProfile});

    const result = await loading;

    expect(result.profile).toEqual({ok: true, data: validProfile});
    expect(result.site).toEqual({ok: true, data: validSite});
    expect(result.experiences).toEqual({
      ok: false,
      failure: {endpoint: 'experiences', kind: 'network'},
    });
    expect(result.workCases).toEqual({ok: true, data: [validWorkCase]});
    expect(result.projects).toEqual({ok: true, data: [validProject]});
    expect(result.technologies).toEqual({ok: true, data: [validTechnology]});
  });

  it('defaults to the real Task 2 fetchers when no dependencies are injected', async () => {
    // With no internal origin configured the real fetchers take their fast
    // `configuration` branch (no network, no timeout wait), which lets us prove
    // the default wiring end-to-end: six keys, each a preserved EndpointResult.
    vi.stubEnv('INTERNAL_API_ORIGIN', '');
    try {
      const result = await loadPublicPortfolioUncached('es');

      expect(Object.keys(result).sort()).toEqual(
        [
          'experiences',
          'profile',
          'projects',
          'site',
          'technologies',
          'workCases',
        ].sort(),
      );
      expect(result.profile).toEqual({
        ok: false,
        failure: {endpoint: 'profile', kind: 'configuration'},
      });
      expect(result.workCases).toEqual({
        ok: false,
        failure: {endpoint: 'work-cases', kind: 'configuration'},
      });
    } finally {
      vi.unstubAllEnvs();
    }
  });
});

describe('loadPublicPortfolio (production export wiring)', () => {
  it('is produced by passing loadPublicPortfolioUncached to React cache()', () => {
    expect(cacheSpy).toHaveBeenCalledTimes(1);
    expect(cacheSpy).toHaveBeenCalledWith(loadPublicPortfolioUncached);
    expect(cacheSpy.mock.results[0]?.value).toBe(loadPublicPortfolio);
  });
});

describe('load-public-portfolio.ts source (no persistent-cache machinery)', () => {
  const source = readFileSync(
    resolve(process.cwd(), 'src/lib/api/load-public-portfolio.ts'),
    'utf8',
  );

  beforeEach(() => {
    // Nothing to reset; these are pure text assertions on the shipped module.
  });

  it('does not use unstable_cache', () => {
    expect(source).not.toMatch(/unstable_cache/);
  });

  it('does not declare a module-level Map/WeakMap result cache', () => {
    expect(source).not.toMatch(/new\s+(Map|WeakMap)\b/);
  });

  it('does not define a hand-rolled memoize helper', () => {
    expect(source).not.toMatch(/memoiz/i);
  });

  it('does not opt into the Next Data Cache via revalidate or cache tags', () => {
    expect(source).not.toMatch(/revalidate|cacheTag|unstable_cacheTag/);
  });
});
