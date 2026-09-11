import type {ReactNode} from 'react';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {renderToStaticMarkup} from 'react-dom/server';
import type {
  EndpointName,
  EndpointResult,
  Profile,
  PublicPortfolioResults,
  SiteConfiguration,
} from '@/lib/api/types';

/**
 * Pins the layout -> header / footer wiring that Task 10 added: the layout
 * consumes the shared request-scoped loader and derives the header `variant`,
 * the Profile-derived identity `name`, and the footer `links` from it. The
 * header's own variant rendering is covered by `components/layout/layout.test.tsx`
 * and the `site-header` tests; this file only proves the layout passes the right
 * props for each loader outcome.
 *
 * The layout returns `<html>`, which RTL unwraps, so — like
 * `boundaries.test.tsx` for `global-error` — it is asserted from server HTML.
 */

const {notFound} = vi.hoisted(() => ({
  notFound: vi.fn(() => {
    throw new Error('NEXT_NOT_FOUND');
  }),
}));

vi.mock('next/navigation', () => ({
  notFound,
  useRouter: () => ({refresh: vi.fn()}),
}));

const {loadPublicPortfolio} = vi.hoisted(() => ({
  loadPublicPortfolio:
    vi.fn<(locale: string) => Promise<PublicPortfolioResults>>(),
}));

vi.mock('@/lib/api/load-public-portfolio', () => ({loadPublicPortfolio}));

vi.mock('next-intl/server', () => ({
  setRequestLocale: vi.fn(),
}));

// The real provider infers `locale` from server request context, which does not
// exist under `renderToStaticMarkup`. Only the shell markup is under test, so a
// passthrough is enough; `hasLocale` and everything else stay real.
vi.mock('next-intl', async (importOriginal) => {
  const actual = await importOriginal<typeof import('next-intl')>();

  return {
    ...actual,
    NextIntlClientProvider: ({children}: {children: ReactNode}) => children,
  };
});

import Layout from './layout';

const PROFILE: Profile = {
  name: 'Synthetic Person Name',
  headline: 'Synthetic headline phrase',
  short_summary: 'Synthetic short summary sentence.',
  introduction: 'Synthetic introduction paragraph.',
  availability: 'Synthetic availability line.',
  cta: 'Synthetic call to action',
  photo: null,
};

const SITE: SiteConfiguration = {
  projects_empty_message: 'Synthetic projects empty message.',
  contact_intro: 'Synthetic contact intro sentence.',
  technology_groups: [
    {key: 'backend', label: 'Synthetic Backend Group'},
    {key: 'data', label: 'Synthetic Data Group'},
    {key: 'integration', label: 'Synthetic Integration Group'},
    {key: 'collaboration', label: 'Synthetic Collaboration Group'},
  ],
  professional_links: [
    {
      key: 'linkedin',
      label: 'Synthetic LinkedIn',
      href: 'https://example.test/in/syn',
    },
    {key: 'email', label: 'Synthetic Email', href: 'mailto:syn@example.test'},
  ],
  expertise_areas: [],
  work_principles: [],
  cv: null,
};

function ok<T>(data: T): EndpointResult<T> {
  return {ok: true, data};
}

function fail<T>(endpoint: EndpointName): EndpointResult<T> {
  return {ok: false, failure: {endpoint, kind: 'network'}};
}

function makeResults(
  overrides: Partial<PublicPortfolioResults> = {},
): PublicPortfolioResults {
  return {
    profile: ok(PROFILE),
    site: ok(SITE),
    experiences: ok([]),
    workCases: ok([]),
    projects: ok([]),
    technologies: ok([]),
    ...overrides,
  };
}

async function renderLayout(
  locale: string,
  results: PublicPortfolioResults = makeResults(),
): Promise<Document> {
  loadPublicPortfolio.mockResolvedValue(results);
  const tree = await Layout({
    children: <div data-testid="child">child</div>,
    params: Promise.resolve({locale}),
  });

  return new DOMParser().parseFromString(
    renderToStaticMarkup(tree),
    'text/html',
  );
}

beforeEach(() => {
  loadPublicPortfolio.mockReset();
  notFound.mockClear();
});

afterEach(() => {
  vi.clearAllMocks();
});

describe('[locale]/layout — shared-loader wiring', () => {
  it('renders the valid header/footer chrome from a fully successful load', async () => {
    const doc = await renderLayout('en');

    expect(loadPublicPortfolio).toHaveBeenCalledTimes(1);
    expect(loadPublicPortfolio).toHaveBeenCalledWith('en');

    const header = doc.querySelector('header.site-header');
    expect(header?.getAttribute('data-variant')).toBe('valid');
    expect(doc.querySelector('#primary-navigation-desktop')).not.toBeNull();
    expect(doc.querySelector('noscript')).not.toBeNull();

    const identity = doc.querySelector('#site-header-home');
    expect(identity?.textContent).toBe(PROFILE.name);
    expect(identity?.getAttribute('href')).toBe('#top');

    expect(doc.querySelector('.site-footer__identity')?.textContent).toBe(
      PROFILE.name,
    );
    const footerLinks = [...doc.querySelectorAll('.site-footer__links a')];
    expect(footerLinks.map((a) => a.getAttribute('href'))).toEqual([
      SITE.professional_links[0].href,
      SITE.professional_links[1].href,
    ]);

    // Persistent shell landmarks are present regardless of variant.
    expect(doc.querySelector('a.skip-link')?.getAttribute('href')).toBe(
      '#main-content',
    );
    expect(doc.querySelector('main#main-content')).not.toBeNull();
    expect(doc.querySelector('[data-testid="child"]')).not.toBeNull();
  });

  // Task 10 brief wiring, tested one predicate at a time:
  //   header `variant`      <- results.profile.ok && results.site.ok
  //   header/footer `name`  <- results.profile.ok
  //   footer `links`        <- results.site.ok
  // So the three failure sub-cases are genuinely distinct.

  function expectStructuralHeader(doc: Document): void {
    expect(
      doc.querySelector('header.site-header')?.getAttribute('data-variant'),
    ).toBe('structural-failure');
    expect(doc.querySelector('#primary-navigation-desktop')).toBeNull();
    expect(doc.querySelector('#primary-navigation-dialog')).toBeNull();
    expect(doc.querySelector('noscript')).toBeNull();
    // Language + theme controls survive the failure header.
    expect(
      doc.querySelector('nav[aria-label="Select language"]'),
    ).not.toBeNull();
  }

  function expectShellLandmarks(doc: Document): void {
    expect(doc.querySelector('a.skip-link')).not.toBeNull();
    expect(doc.querySelector('main#main-content')).not.toBeNull();
    expect(doc.querySelector('[data-testid="child"]')).not.toBeNull();
  }

  it('Profile failed only: structural header, no identity name, but footer links kept (Site still ok)', async () => {
    const doc = await renderLayout(
      'en',
      makeResults({profile: fail<Profile>('profile')}),
    );

    expectStructuralHeader(doc);
    expect(doc.querySelector('#site-header-home')).toBeNull();
    expect(doc.querySelector('.site-footer__identity')).toBeNull();
    expect(doc.querySelectorAll('.site-footer__links a')).toHaveLength(
      SITE.professional_links.length,
    );
    expectShellLandmarks(doc);
  });

  it('Site failed only: structural header, footer links dropped, but the valid Profile identity is kept', async () => {
    const doc = await renderLayout(
      'en',
      makeResults({site: fail<SiteConfiguration>('site')}),
    );

    expectStructuralHeader(doc);
    expect(doc.querySelector('#site-header-home')?.textContent).toBe(
      PROFILE.name,
    );
    expect(doc.querySelector('.site-footer__identity')?.textContent).toBe(
      PROFILE.name,
    );
    expect(doc.querySelector('.site-footer__links')).toBeNull();
    expectShellLandmarks(doc);
  });

  it('Profile and Site both failed: structural header, no identity name, no footer links', async () => {
    const doc = await renderLayout(
      'en',
      makeResults({
        profile: fail<Profile>('profile'),
        site: fail<SiteConfiguration>('site'),
      }),
    );

    expectStructuralHeader(doc);
    expect(doc.querySelector('#site-header-home')).toBeNull();
    expect(doc.querySelector('.site-footer__identity')).toBeNull();
    expect(doc.querySelector('.site-footer__links')).toBeNull();
    expectShellLandmarks(doc);
  });

  it('calls notFound() and never touches the loader for an unsupported locale', async () => {
    await expect(
      Layout({
        children: <div data-testid="child" />,
        params: Promise.resolve({locale: 'fr'}),
      }),
    ).rejects.toThrow('NEXT_NOT_FOUND');

    expect(notFound).toHaveBeenCalledOnce();
    expect(loadPublicPortfolio).not.toHaveBeenCalled();
  });
});
