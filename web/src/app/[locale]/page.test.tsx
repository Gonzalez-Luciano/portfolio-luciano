import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {cleanup, render, screen, within} from '@testing-library/react';
import {NextIntlClientProvider} from 'next-intl';
import type {
  EndpointFailureKind,
  EndpointName,
  EndpointResult,
  Experience,
  Profile,
  Project,
  PublicPortfolioResults,
  SiteConfiguration,
  Technology,
  WorkCase,
} from '@/lib/api/types';
import englishMessages from '../../../messages/en.json';
import spanishMessages from '../../../messages/es.json';

/**
 * Route composition contract for Task 10 (design spec §13–§17, §20, §28).
 *
 * Only the shared loader and `next/navigation` are mocked. Every assertion is
 * against the real rendered DOM produced by the real section components and the
 * real `Portfolio` message catalog — never a mock shape.
 */

const {notFound} = vi.hoisted(() => ({
  notFound: vi.fn(() => {
    throw new Error('NEXT_NOT_FOUND');
  }),
}));

vi.mock('next/navigation', () => ({
  notFound,
  // RetryButton (rendered inside every regional/structural failure surface) is
  // a Client Component that calls useRouter().refresh().
  useRouter: () => ({refresh: vi.fn()}),
}));

const {loadPublicPortfolio} = vi.hoisted(() => ({
  loadPublicPortfolio: vi.fn<(locale: string) => Promise<PublicPortfolioResults>>(),
}));

vi.mock('@/lib/api/load-public-portfolio', () => ({loadPublicPortfolio}));

vi.mock('next-intl/server', () => ({
  setRequestLocale: vi.fn(),
  getTranslations: async ({locale}: {locale: string; namespace?: string}) => {
    const catalog = (locale === 'es' ? spanishMessages : englishMessages)
      .Portfolio as unknown as Record<string, unknown>;

    return (key: string): string => {
      const resolved = key.split('.').reduce<unknown>((node, segment) => {
        if (node && typeof node === 'object') {
          return (node as Record<string, unknown>)[segment];
        }

        return undefined;
      }, catalog);

      return typeof resolved === 'string' ? resolved : key;
    };
  },
}));

import Page, {generateMetadata, generateStaticParams} from './page';

// --------------------------------------------------------------------------
// Synthetic fixtures — deliberately non-professional. No real name / claim.
// --------------------------------------------------------------------------

const PROFILE: Profile = {
  name: 'Synthetic Person Name',
  headline: 'Synthetic headline phrase',
  short_summary: 'Synthetic short summary sentence.',
  introduction:
    'Synthetic introduction paragraph one.\n\nSynthetic introduction paragraph two.',
  availability: 'Synthetic availability line.',
  cta: 'Synthetic call to action',
  photo: null,
};

const TECHNOLOGY: Technology = {
  key: 'syn-tech',
  name: 'Synthetic Technology',
  category: 'backend',
  icon: null,
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
    {key: 'linkedin', label: 'Synthetic LinkedIn', href: 'https://example.test/in/syn'},
    {key: 'email', label: 'Synthetic Email', href: 'mailto:syn@example.test'},
  ],
  expertise_areas: [
    {key: 'syn-area', title: 'Synthetic Area Title', description: 'Synthetic area description.'},
  ],
  work_principles: [{key: 'syn-principle', statement: 'Synthetic principle statement.'}],
  cv: {url: '/cv/syn.pdf', label: 'Synthetic CV'},
};

const EXPERIENCE: Experience = {
  key: 'syn-exp',
  organization: 'Synthetic Organization',
  role: 'Synthetic Role',
  start: '2021-03',
  end: '2023-07',
  summary: 'Synthetic experience summary.',
  highlights: ['Synthetic highlight one.'],
  technologies: [],
};

const WORK_CASE: WorkCase = {
  key: 'syn-wc',
  title: 'Synthetic Work Case Title',
  context: 'Synthetic work case context.',
  problem: 'Synthetic work case problem.',
  contribution: 'Synthetic work case contribution.',
  technical_approach: 'Synthetic work case technical approach.',
  outcome: 'Synthetic work case outcome.',
  technologies: [],
};

const PROJECT: Project = {
  key: 'syn-proj',
  title: 'Synthetic Project Title',
  summary: 'Synthetic project summary.',
  problem: 'Synthetic project problem.',
  solution: 'Synthetic project solution.',
  featured: false,
  image: null,
  demo_url: null,
  repository_url: null,
  technologies: [],
};

function ok<T>(data: T): EndpointResult<T> {
  return {ok: true, data};
}

function fail<T>(
  endpoint: EndpointName,
  kind: EndpointFailureKind = 'network',
): EndpointResult<T> {
  return {ok: false, failure: {endpoint, kind}};
}

function makeResults(
  overrides: Partial<PublicPortfolioResults> = {},
): PublicPortfolioResults {
  return {
    profile: ok(PROFILE),
    site: ok(SITE),
    experiences: ok([EXPERIENCE]),
    workCases: ok([WORK_CASE]),
    projects: ok([PROJECT]),
    technologies: ok([TECHNOLOGY]),
    ...overrides,
  };
}

async function renderPage(
  locale: 'es' | 'en',
  results: PublicPortfolioResults = makeResults(),
) {
  loadPublicPortfolio.mockResolvedValue(results);
  const messages = locale === 'es' ? spanishMessages : englishMessages;
  const ui = await Page({params: Promise.resolve({locale})});

  return render(
    <NextIntlClientProvider locale={locale} messages={messages}>
      {ui}
    </NextIntlClientProvider>,
  );
}

function copy(locale: 'es' | 'en') {
  return (locale === 'es' ? spanishMessages : englishMessages).Portfolio.state;
}

function orderOf(html: string, ...ids: string[]): number[] {
  return ids.map((id) => html.indexOf(`id="${id}"`));
}

const PRIMARY_ANCHORS = ['work', 'expertise', 'projects', 'approach', 'contact'];

beforeEach(() => {
  loadPublicPortfolio.mockReset();
  notFound.mockClear();
});

afterEach(cleanup);

function getKeys(value: Record<string, unknown>, prefix = ''): string[] {
  return Object.entries(value).flatMap(([key, child]) => {
    const path = prefix ? `${prefix}.${key}` : key;

    return typeof child === 'object' && child !== null
      ? getKeys(child as Record<string, unknown>, path)
      : [path];
  });
}

describe('localized route — locale plumbing', () => {
  it('prebuilds exactly the supported locale routes', () => {
    expect(generateStaticParams()).toEqual([{locale: 'es'}, {locale: 'en'}]);
  });

  it('rejects unknown locale route parameters before touching the loader', async () => {
    await expect(
      Page({params: Promise.resolve({locale: 'fr'})}),
    ).rejects.toThrow('NEXT_NOT_FOUND');
    expect(notFound).toHaveBeenCalledOnce();
    expect(loadPublicPortfolio).not.toHaveBeenCalled();
  });

  it('keeps the Spanish and English portfolio message keys aligned', () => {
    expect(getKeys(spanishMessages)).toEqual(getKeys(englishMessages));
  });
});

describe.each(['es', 'en'] as const)(
  'structurally valid page — six successes (%s)',
  (locale) => {
    it('renders the full section composition with one h1 and the five primary anchors in order', async () => {
      const {container} = await renderPage(locale);

      const h1 = screen.getAllByRole('heading', {level: 1});
      expect(h1).toHaveLength(1);
      expect(h1[0]).toHaveTextContent(PROFILE.name);

      const cta = screen.getByRole('link', {name: PROFILE.cta});
      expect(cta).toHaveAttribute('href', '#work');

      for (const id of ['about', ...PRIMARY_ANCHORS]) {
        expect(container.querySelector(`section#${id}`)).not.toBeNull();
      }

      const positions = orderOf(container.innerHTML, ...PRIMARY_ANCHORS);
      expect(positions).toEqual([...positions].sort((a, b) => a - b));
      expect(positions.every((value) => value > -1)).toBe(true);
    });

    it('orders Work Cases before Experience', async () => {
      const {container} = await renderPage(locale);

      const [wc, exp] = orderOf(container.innerHTML, 'work-cases', 'experience');
      expect(wc).toBeGreaterThan(-1);
      expect(exp).toBeGreaterThan(-1);
      expect(wc).toBeLessThan(exp);
    });

    it('renders real Projects content, not the Site empty message, and no structural-failure copy', async () => {
      const {container} = await renderPage(locale);

      const projects = container.querySelector('section#projects') as HTMLElement;
      expect(within(projects).getByText(PROJECT.title)).toBeInTheDocument();
      expect(
        within(projects).queryByText(SITE.projects_empty_message),
      ).not.toBeInTheDocument();

      expect(
        screen.queryByText(copy(locale).structuralFailure),
      ).not.toBeInTheDocument();
      expect(
        screen.queryByRole('button', {name: copy(locale).retry}),
      ).not.toBeInTheDocument();
    });
  },
);

describe.each([
  ['profile', {profile: fail<Profile>('profile')}],
  ['site', {site: fail<SiteConfiguration>('site')}],
  ['profile and site', {
    profile: fail<Profile>('profile'),
    site: fail<SiteConfiguration>('site'),
  }],
  ['malformed profile', {profile: fail<Profile>('profile', 'malformed')}],
] as const)('structural failure — %s failed', (_label, override) => {
  it('renders only the technical structural-failure surface, no professional landmarks', async () => {
    const {container} = await renderPage(
      'en',
      makeResults(override as Partial<PublicPortfolioResults>),
    );

    expect(
      screen.getByRole('heading', {name: copy('en').structuralFailure}),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {name: copy('en').retry}),
    ).toBeInTheDocument();

    expect(screen.queryAllByRole('heading', {level: 1})).toHaveLength(0);
    expect(container.querySelector('section#top')).toBeNull();
    for (const id of ['about', ...PRIMARY_ANCHORS]) {
      expect(container.querySelector(`section#${id}`)).toBeNull();
    }

    // No hardcoded professional fallback leaked into the shell.
    expect(screen.queryByText(PROFILE.name)).not.toBeInTheDocument();
    expect(screen.queryByText(PROJECT.title)).not.toBeInTheDocument();
    expect(screen.queryByText(SITE.contact_intro)).not.toBeInTheDocument();
  });
});

describe('valid Profile + failed Site', () => {
  it('is a structural failure because Site is load-bearing for every section', async () => {
    const {container} = await renderPage(
      'es',
      makeResults({site: fail<SiteConfiguration>('site')}),
    );

    expect(
      screen.getByRole('heading', {name: copy('es').structuralFailure}),
    ).toBeInTheDocument();
    expect(container.querySelector('section#work')).toBeNull();
    expect(container.querySelector('section#top')).toBeNull();
  });
});

describe('regional failure — one collection failed', () => {
  it('renders every section; the failed region keeps its anchor and a Retry control', async () => {
    const {container} = await renderPage(
      'en',
      makeResults({workCases: fail<WorkCase[]>('work-cases')}),
    );

    expect(screen.getAllByRole('heading', {level: 1})).toHaveLength(1);
    for (const id of ['about', ...PRIMARY_ANCHORS]) {
      expect(container.querySelector(`section#${id}`)).not.toBeNull();
    }

    const work = container.querySelector('section#work') as HTMLElement;
    expect(work.querySelector('section#work-cases')).not.toBeNull();
    expect(
      within(work).getAllByRole('button', {name: copy('en').retry}).length,
    ).toBeGreaterThan(0);

    // A healthy neighbour is untouched.
    const projects = container.querySelector('section#projects') as HTMLElement;
    expect(within(projects).getByText(PROJECT.title)).toBeInTheDocument();
    // Experience (the healthy Work sibling) still renders its content.
    expect(within(work).getByText(EXPERIENCE.summary)).toBeInTheDocument();
  });

  it('treats a malformed collection result as a regional failure', async () => {
    const {container} = await renderPage(
      'en',
      makeResults({technologies: fail<Technology[]>('technologies', 'malformed')}),
    );

    expect(screen.getAllByRole('heading', {level: 1})).toHaveLength(1);
    const expertise = container.querySelector('section#expertise') as HTMLElement;
    expect(expertise.querySelector('section#technologies')).not.toBeNull();
    expect(
      within(expertise).getByRole('button', {name: copy('en').retry}),
    ).toBeInTheDocument();
  });
});

describe('regional failure — simultaneous collection failures', () => {
  it('still renders all sections; each failed region keeps its anchor + Retry; #projects shows regional copy, not the empty message', async () => {
    const {container} = await renderPage(
      'en',
      makeResults({
        workCases: fail<WorkCase[]>('work-cases'),
        projects: fail<Project[]>('projects'),
        technologies: fail<Technology[]>('technologies'),
      }),
    );

    for (const id of ['about', ...PRIMARY_ANCHORS]) {
      expect(container.querySelector(`section#${id}`)).not.toBeNull();
    }

    const projects = container.querySelector('section#projects') as HTMLElement;
    expect(
      within(projects).getByText(copy('en').regionalFailure),
    ).toBeInTheDocument();
    expect(
      within(projects).queryByText(SITE.projects_empty_message),
    ).not.toBeInTheDocument();

    expect(
      screen.getAllByRole('button', {name: copy('en').retry}).length,
    ).toBeGreaterThanOrEqual(3);
    expect(
      (container.querySelector('section#work') as HTMLElement).querySelector(
        'section#work-cases',
      ),
    ).not.toBeNull();
    expect(
      (container.querySelector('section#expertise') as HTMLElement).querySelector(
        'section#technologies',
      ),
    ).not.toBeNull();
  });
});

describe('empty-state — every collection legitimately empty', () => {
  it('keeps the five primary anchors in order and applies each group its own empty policy', async () => {
    const {container} = await renderPage(
      'en',
      makeResults({
        experiences: ok<Experience[]>([]),
        workCases: ok<WorkCase[]>([]),
        projects: ok<Project[]>([]),
        technologies: ok<Technology[]>([]),
        site: ok<SiteConfiguration>({
          ...SITE,
          expertise_areas: [],
          work_principles: [],
        }),
      }),
    );

    const positions = orderOf(container.innerHTML, ...PRIMARY_ANCHORS);
    expect(positions).toEqual([...positions].sort((a, b) => a - b));
    expect(positions.every((value) => value > -1)).toBe(true);

    const neutral = copy('en').neutralEmpty;

    const work = container.querySelector('section#work') as HTMLElement;
    expect(work.querySelector('section#work-cases')).toBeNull();
    expect(work.querySelector('section#experience')).toBeNull();
    expect(within(work).getByText(neutral)).toBeInTheDocument();

    const projects = container.querySelector('section#projects') as HTMLElement;
    expect(
      within(projects).getByText(SITE.projects_empty_message),
    ).toBeInTheDocument();
    expect(within(projects).queryByText(neutral)).not.toBeInTheDocument();

    expect(
      within(container.querySelector('section#approach') as HTMLElement).getByText(
        neutral,
      ),
    ).toBeInTheDocument();
    expect(
      within(container.querySelector('section#expertise') as HTMLElement).getByText(
        neutral,
      ),
    ).toBeInTheDocument();

    // Neutral empty groups carry no Retry control.
    expect(
      screen.queryByRole('button', {name: copy('en').retry}),
    ).not.toBeInTheDocument();
  });
});

describe('generateMetadata — basic Profile boundary (spec §28)', () => {
  it.each(['es', 'en'] as const)(
    'uses the exact `${name} — ${headline}` title and short summary for a valid Profile (%s)',
    async (locale) => {
      loadPublicPortfolio.mockResolvedValue(makeResults());

      const metadata = await generateMetadata({
        params: Promise.resolve({locale}),
      });

      expect(metadata).toEqual({
        title: 'Synthetic Person Name — Synthetic headline phrase',
        description: 'Synthetic short summary sentence.',
      });
    },
  );

  it('falls back to the localized unavailable title with NO description when Profile failed', async () => {
    loadPublicPortfolio.mockResolvedValue(
      makeResults({profile: fail<Profile>('profile')}),
    );

    const es = await generateMetadata({params: Promise.resolve({locale: 'es'})});
    expect(es).toEqual({title: 'Portfolio no disponible'});
    expect(es).not.toHaveProperty('description');

    loadPublicPortfolio.mockResolvedValue(
      makeResults({profile: fail<Profile>('profile', 'malformed')}),
    );
    const en = await generateMetadata({params: Promise.resolve({locale: 'en'})});
    expect(en).toEqual({title: 'Portfolio unavailable'});
    expect(en).not.toHaveProperty('description');
  });

  it('still emits valid Profile metadata when only Site failed', async () => {
    loadPublicPortfolio.mockResolvedValue(
      makeResults({site: fail<SiteConfiguration>('site')}),
    );

    const metadata = await generateMetadata({
      params: Promise.resolve({locale: 'en'}),
    });

    expect(metadata).toEqual({
      title: 'Synthetic Person Name — Synthetic headline phrase',
      description: 'Synthetic short summary sentence.',
    });
  });
});

describe('loader sharing (module wiring only — Task 13 owns the real acquisition-count proof)', () => {
  it('routes the page and generateMetadata through the same loadPublicPortfolio export with the same locale', async () => {
    loadPublicPortfolio.mockResolvedValue(makeResults());

    await Page({params: Promise.resolve({locale: 'en'})});
    await generateMetadata({params: Promise.resolve({locale: 'en'})});

    expect(loadPublicPortfolio.mock.calls.map((call) => call[0])).toEqual([
      'en',
      'en',
    ]);
  });
});
