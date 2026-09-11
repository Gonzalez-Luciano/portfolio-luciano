import type {ReactElement, ReactNode} from 'react';
import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';
import {renderToString} from 'react-dom/server';
import {hydrateRoot, type Root} from 'react-dom/client';
import {act, fireEvent, screen, within} from '@testing-library/react';
import type {
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
 * Task 14 — hydration / client-island integration proof (design spec §7, §19,
 * §22, §26, §30.1).
 *
 * This file server-renders the REAL localized layout + page composition (the
 * same `LocaleLayout`/`LocalePage` modules the app ships, with only the shared
 * loader and Next/`next-intl` server plumbing mocked — never a stand-in shell)
 * and then hydrates that exact server HTML with `hydrateRoot` inside jsdom. It
 * proves the composed surface — SiteHeader (theme + language + mobile dialog),
 * the WorkGroup's Indexed Detail widget, FragmentFocusManager, and the site
 * shell — hydrates with:
 *
 *   - no hydration mismatch/warning printed through `console.error`;
 *   - no uncaught `window.onerror` exception;
 *   - no `unhandledrejection`;
 *
 * while exercising the documented client interactions: theme toggle, Indexed
 * Detail tab selection (desktop, 3 Work Cases), the mobile dialog's
 * open/close/locale-selection lifecycle, and a mocked `(min-width: 64rem)`
 * breakpoint transition in both directions.
 *
 * jsdom ceiling (explicitly NOT asserted here, per spec §30.1 — Task 15
 * real-browser QA owns these): native `<dialog>` modality / real focus
 * trapping, real scroll or fragment positioning, the real 64rem media query,
 * real Next.js image-optimizer network behavior, browser zoom, and
 * screen-reader output. jsdom's stub `HTMLDialogElement` is polyfilled just
 * enough (same pattern as `mobile-navigation.test.tsx`) to observe the `open`
 * attribute and the `close` event.
 *
 * ---- Harness shape ----
 *
 * 1. `LocalePage({params})` is awaited to a resolved element tree, then handed
 *    as `children` to the awaited `LocaleLayout({children, params})` — the
 *    exact nesting production uses (the layout, not the page, owns
 *    `NextIntlClientProvider`, the skip link, `SiteHeader`, `<main>`,
 *    `SiteFooter`, and `FragmentFocusManager`).
 * 2. `renderToString` produces the server HTML for that tree.
 * 3. `installServerDocument` parses that HTML with `DOMParser` and copies it
 *    (attributes included) onto the real `document` — `<html lang>`, `<head>`,
 *    and `<body className>` — because setting `documentElement.innerHTML`
 *    alone does not carry element attributes.
 * 4. The theme is then pre-applied by hand — `document.documentElement.dataset
 *    .theme` / `.dataset.js = 'ready'` — mirroring exactly what the real
 *    pre-paint `<script>` (`theme/bootstrap.ts`) does before hydration and
 *    BEFORE any React code runs; the layout's `<html suppressHydrationWarning>`
 *    is what makes that safe (design spec §9, §19).
 * 5. `hydrateRoot(document, tree)` hydrates the whole document (React 19
 *    supports `document` as a hydration container for a tree rooted at
 *    `<html>`). Both the initial hydrate and every later `root.unmount()` are
 *    wrapped in `await act(async () => { ...; await Promise.resolve(); })` —
 *    calling `unmount()` synchronously right after `hydrateRoot` (with no
 *    microtask flush) trips React's "received an early update before
 *    anything was able to hydrate" client-render fallback; awaiting one
 *    microtask lets the hydration commit finish first.
 * 6. `console.error` is spied on, and `window` listens for `error` and
 *    `unhandledrejection`, all installed BEFORE step 5 and asserted after
 *    every interaction.
 *
 * Because every scenario mounts onto the single jsdom `document`, each test
 * unmounts its root and calls `resetDocument()` in `afterEach` so scenarios
 * never leak DOM/attributes into one another.
 */

// --------------------------------------------------------------------------
// Module mocks — the same seams `layout.test.tsx` / `page.test.tsx` already
// use. `next-intl`'s `NextIntlClientProvider` is stubbed to a passthrough for
// the same reason `layout.test.tsx` documents: the real provider infers
// `locale` from Next's server request context (AsyncLocalStorage), which does
// not exist under Vitest/jsdom. None of the exercised client islands call
// `useTranslations`/`useLocale` themselves — every label reaches them as a
// prop from the server tree — so the real intl *context* is never on the
// critical path for what this file proves.
// --------------------------------------------------------------------------

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

vi.mock('next-intl', async (importOriginal) => {
  const actual = await importOriginal<typeof import('next-intl')>();

  return {
    ...actual,
    NextIntlClientProvider: ({children}: {children: ReactNode}) => children,
  };
});

import LocaleLayout from './layout';
import LocalePage from './page';

// --------------------------------------------------------------------------
// jsdom `HTMLDialogElement` polyfill — identical to
// `components/layout/mobile-navigation.test.tsx`. jsdom's stub does not
// implement `showModal`/`close`/native `cancel`; this polyfill only tracks the
// `open` attribute and fires the `close` event so the coordinator's state
// syncing is observable. It proves nothing about real modality.
// --------------------------------------------------------------------------

const showModal = vi.fn(function showModalPolyfill(this: HTMLDialogElement) {
  this.setAttribute('open', '');
});
const closeDialog = vi.fn(function closePolyfill(this: HTMLDialogElement) {
  if (!this.hasAttribute('open')) {
    return;
  }

  this.removeAttribute('open');
  this.dispatchEvent(new Event('close'));
});

beforeAll(() => {
  HTMLDialogElement.prototype.showModal = showModal;
  HTMLDialogElement.prototype.close = closeDialog;
});

// --------------------------------------------------------------------------
// Mocked `(min-width: 64rem)` media query — shared by IndexedWorkCases and
// MobileNavigation, exactly as one real `MediaQueryList` instance per query
// string would be in a browser. `emit` dispatches a `change` event to every
// listener registered by either component.
// --------------------------------------------------------------------------

const DESKTOP_QUERY = '(min-width: 64rem)';
type MediaListener = (event: MediaQueryListEvent) => void;

function installMatchMedia(initialMatches: boolean) {
  const listeners = new Set<MediaListener>();
  const mql = {
    matches: initialMatches,
    media: DESKTOP_QUERY,
    addEventListener: (_type: 'change', listener: MediaListener) =>
      listeners.add(listener),
    removeEventListener: (_type: 'change', listener: MediaListener) =>
      listeners.delete(listener),
  };

  vi.stubGlobal(
    'matchMedia',
    vi.fn(() => mql as unknown as MediaQueryList),
  );

  return {
    get matches() {
      return mql.matches;
    },
    emit(matches: boolean) {
      mql.matches = matches;
      act(() => {
        listeners.forEach((listener) =>
          listener({matches} as MediaQueryListEvent),
        );
      });
    },
  };
}

// --------------------------------------------------------------------------
// Synthetic fixtures — deliberately non-professional (spec §29, never a real
// claim). Three Work Cases so Indexed Detail gets real desktop tab semantics
// (count >= 2), and every other collection non-empty so every section's
// content path — not its empty/failure path, already covered elsewhere — is
// exercised during hydration.
// --------------------------------------------------------------------------

const PROFILE: Profile = {
  name: 'Synthetic Hydration Person',
  headline: 'Synthetic hydration headline',
  short_summary: 'Synthetic hydration short summary.',
  introduction: 'Synthetic hydration introduction paragraph.',
  availability: 'Synthetic hydration availability line.',
  cta: 'Synthetic hydration CTA',
  photo: null,
};

const TECHNOLOGY: Technology = {
  key: 'syn-hydration-tech',
  name: 'Synthetic Hydration Technology',
  category: 'backend',
  icon: null,
};

const SITE: SiteConfiguration = {
  projects_empty_message: 'Synthetic hydration projects empty message.',
  contact_intro: 'Synthetic hydration contact intro.',
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
    {
      key: 'github',
      label: 'Synthetic GitHub',
      href: 'https://example.test/syn',
    },
    {key: 'email', label: 'Synthetic Email', href: 'mailto:syn@example.test'},
  ],
  expertise_areas: [
    {
      key: 'syn-area-1',
      title: 'Synthetic Area One',
      description: 'Synthetic area one description.',
    },
    {key: 'syn-area-2', title: 'Synthetic Area Two', description: null},
  ],
  work_principles: [
    {key: 'syn-principle-1', statement: 'Synthetic principle one.'},
    {key: 'syn-principle-2', statement: 'Synthetic principle two.'},
  ],
  cv: {url: '/cv/syn-hydration.pdf', label: 'Synthetic CV'},
};

const EXPERIENCES: Experience[] = [
  {
    key: 'syn-exp-1',
    organization: 'Synthetic Organization One',
    role: 'Synthetic Role One',
    start: '2021-03',
    end: '2023-07',
    summary: 'Synthetic experience summary one.',
    highlights: ['Synthetic highlight one.'],
    technologies: [TECHNOLOGY],
  },
  {
    key: 'syn-exp-2',
    organization: 'Synthetic Organization Two',
    role: 'Synthetic Role Two',
    start: '2023-08',
    end: null,
    summary: 'Synthetic experience summary two.',
    highlights: ['Synthetic highlight two.'],
    technologies: [],
  },
];

function makeWorkCase(n: number): WorkCase {
  return {
    key: `syn-wc-${n}`,
    title: `Synthetic Work Case Title ${n}`,
    context: `Synthetic work case ${n} context.`,
    problem: `Synthetic work case ${n} problem.`,
    contribution: `Synthetic work case ${n} contribution.`,
    technical_approach: `Synthetic work case ${n} technical approach.`,
    outcome: `Synthetic work case ${n} outcome.`,
    technologies: [],
  };
}

const WORK_CASES: WorkCase[] = [
  makeWorkCase(1),
  makeWorkCase(2),
  makeWorkCase(3),
];

const PROJECTS: Project[] = [
  {
    key: 'syn-proj-1',
    title: 'Synthetic Project Title One',
    summary: 'Synthetic project summary one.',
    problem: 'Synthetic project problem one.',
    solution: 'Synthetic project solution one.',
    featured: true,
    image: null,
    demo_url: null,
    repository_url: null,
    technologies: [TECHNOLOGY],
  },
  {
    key: 'syn-proj-2',
    title: 'Synthetic Project Title Two',
    summary: 'Synthetic project summary two.',
    problem: 'Synthetic project problem two.',
    solution: 'Synthetic project solution two.',
    featured: false,
    image: null,
    demo_url: 'https://example.test/demo',
    repository_url: 'https://example.test/repo',
    technologies: [],
  },
];

function ok<T>(data: T): EndpointResult<T> {
  return {ok: true, data};
}

function makeResults(): PublicPortfolioResults {
  return {
    profile: ok(PROFILE),
    site: ok(SITE),
    experiences: ok(EXPERIENCES),
    workCases: ok(WORK_CASES),
    projects: ok(PROJECTS),
    technologies: ok([TECHNOLOGY]),
  };
}

// --------------------------------------------------------------------------
// Document install / reset helpers.
// --------------------------------------------------------------------------

function installServerDocument(html: string): void {
  const parsed = new DOMParser().parseFromString(html, 'text/html');

  for (const attr of Array.from(parsed.documentElement.attributes)) {
    document.documentElement.setAttribute(attr.name, attr.value);
  }
  for (const attr of Array.from(parsed.head.attributes)) {
    document.head.setAttribute(attr.name, attr.value);
  }
  for (const attr of Array.from(parsed.body.attributes)) {
    document.body.setAttribute(attr.name, attr.value);
  }

  document.head.innerHTML = parsed.head.innerHTML;
  document.body.innerHTML = parsed.body.innerHTML;
}

function resetDocument(): void {
  for (const attr of Array.from(document.documentElement.attributes)) {
    document.documentElement.removeAttribute(attr.name);
  }
  for (const attr of Array.from(document.body.attributes)) {
    document.body.removeAttribute(attr.name);
  }
  document.head.innerHTML = '';
  document.body.innerHTML = '';
  window.location.hash = '';
}

type Theme = 'light' | 'dark';

async function mountScenario(
  locale: 'es' | 'en',
  theme: Theme,
  hash = '',
): Promise<Root> {
  loadPublicPortfolio.mockResolvedValue(makeResults());

  if (hash) {
    window.location.hash = hash;
  }

  const pageTree = await LocalePage({params: Promise.resolve({locale})});
  const layoutTree = await LocaleLayout({
    children: pageTree,
    params: Promise.resolve({locale}),
  });

  const html = renderToString(layoutTree as ReactElement);
  installServerDocument(html);

  // Mirrors `theme/bootstrap.ts`: applied to the real DOM before hydration,
  // outside the React tree. `<html suppressHydrationWarning>` in layout.tsx is
  // what makes this attribute divergence a non-issue for React.
  document.documentElement.dataset.theme = theme;
  document.documentElement.dataset.js = 'ready';

  let root!: Root;
  await act(async () => {
    root = hydrateRoot(document, layoutTree as ReactElement);
    await Promise.resolve();
  });

  return root;
}

async function unmountScenario(root: Root): Promise<void> {
  await act(async () => {
    root.unmount();
    await Promise.resolve();
  });
  resetDocument();
}

// --------------------------------------------------------------------------
// Diagnostic spies — installed before every scenario, asserted after.
// --------------------------------------------------------------------------

let consoleErrorSpy: ReturnType<typeof vi.spyOn>;
let windowErrors: unknown[];
let unhandledRejections: unknown[];

function onWindowError(event: ErrorEvent) {
  windowErrors.push(event.error ?? event.message);
}

function onUnhandledRejection(event: PromiseRejectionEvent) {
  unhandledRejections.push(event.reason);
}

beforeEach(() => {
  windowErrors = [];
  unhandledRejections = [];
  consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
  window.addEventListener('error', onWindowError);
  window.addEventListener('unhandledrejection', onUnhandledRejection);
});

afterEach(() => {
  window.removeEventListener('error', onWindowError);
  window.removeEventListener('unhandledrejection', onUnhandledRejection);
  consoleErrorSpy.mockRestore();
  vi.unstubAllGlobals();
  loadPublicPortfolio.mockReset();
  notFound.mockClear();
  showModal.mockClear();
  closeDialog.mockClear();
  document.body.style.overflow = '';
});

function expectClean(): void {
  expect(consoleErrorSpy).not.toHaveBeenCalled();
  expect(windowErrors).toEqual([]);
  expect(unhandledRejections).toEqual([]);
}

function labelsFor(locale: 'es' | 'en') {
  return (locale === 'es' ? spanishMessages : englishMessages).Portfolio;
}

// ==========================================================================
// Full exercise — every documented interaction, once. Spanish, light theme,
// starting at the `(min-width: 64rem)` desktop breakpoint so Indexed Detail's
// tab semantics are present from the first hydrated paint.
// ==========================================================================

describe('hydration — full interaction exercise (es, light, desktop-first)', () => {
  it('server-renders and hydrates the real composed page with zero diagnostics through every documented interaction', async () => {
    const media = installMatchMedia(true);
    const t = labelsFor('es');

    const root = await mountScenario('es', 'light', '#work');
    expectClean();

    // ---- shell sanity: this is the real composed tree, not a stand-in ----
    expect(document.documentElement.getAttribute('lang')).toBe('es');
    expect(document.documentElement.dataset.theme).toBe('light');
    expect(screen.getAllByRole('heading', {level: 1})).toHaveLength(1);
    expect(document.querySelectorAll('dialog')).toHaveLength(1);

    // FragmentFocusManager (mounted inside the layout) focuses the `#work`
    // fragment target on hydration because `#work` is one of the eleven
    // allowlisted ids and `location.hash` was `#work` before hydrateRoot ran.
    expect(document.activeElement?.id).toBe('work');

    // ---- Indexed Detail: desktop tabs present for 3 Work Cases ----
    const tabs = screen.getAllByRole('tab');
    expect(tabs).toHaveLength(3);
    expect(tabs[0]).toHaveAttribute('aria-selected', 'true');

    fireEvent.click(tabs[1]);
    expect(tabs[1]).toHaveAttribute('aria-selected', 'true');
    expect(tabs[0]).toHaveAttribute('aria-selected', 'false');
    expectClean();

    tabs[1].focus();
    fireEvent.keyDown(tabs[1], {key: 'ArrowDown'});
    expect(tabs[2]).toHaveAttribute('aria-selected', 'true');
    expect(document.activeElement).toBe(tabs[2]);
    expectClean();

    // ---- theme toggle ----
    const themeSection = screen.getByRole('region', {
      name: t.theme.label,
    }) as HTMLElement;
    const darkButton = within(themeSection).getByRole('button', {
      name: t.theme.dark,
    });
    fireEvent.click(darkButton);
    expect(document.documentElement.dataset.theme).toBe('dark');
    expect(darkButton).toHaveAttribute('aria-pressed', 'true');
    expectClean();

    // ---- mobile dialog: open, locale-switch selection closes it ----
    const menuTrigger = screen.getByRole('button', {name: t.menu.open});
    fireEvent.click(menuTrigger);
    expect(showModal).toHaveBeenCalled();
    expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(true);
    expectClean();

    const dialog = document.querySelector('dialog') as HTMLElement;
    const englishLink = within(dialog).getByRole('link', {name: 'English'});
    // jsdom does not implement real cross-path navigation; a plain click would
    // log a "Not implemented: navigation" console error unrelated to hydration.
    // Prevent the default the same way `mobile-navigation.test.tsx` does, so
    // only the component's own `onClick` (cookie write + dialog close) runs.
    const inertClick = new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
    });
    inertClick.preventDefault();
    fireEvent(englishLink, inertClick);
    expect(closeDialog).toHaveBeenCalled();
    expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(false);
    expect(document.cookie).toContain('portfolio_locale=en');
    expectClean();

    // ---- mobile dialog: explicit open + Close ----
    fireEvent.click(menuTrigger);
    expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(true);
    fireEvent.click(within(dialog).getByRole('button', {name: t.menu.close}));
    expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(false);
    expect(document.activeElement).toBe(menuTrigger);
    expectClean();

    // ---- breakpoint transition: narrow, then back to desktop ----
    // Crossing below 64rem: Indexed Detail drops tab semantics and every
    // dossier becomes sequential again.
    media.emit(false);
    expect(screen.queryByRole('tablist')).toBeNull();
    expect(screen.queryAllByRole('tab')).toHaveLength(0);
    for (const workCase of WORK_CASES) {
      expect(screen.getByText(workCase.outcome)).toBeInTheDocument();
    }
    expectClean();

    // Open the dialog while narrow, then cross back to desktop: the dialog
    // must close itself and move focus to a visible header target — never
    // leave focus inside now-hidden content, never restore it to the hidden
    // trigger (design spec §19).
    fireEvent.click(menuTrigger);
    expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(true);

    media.emit(true);
    expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(false);
    // Focus lands on the visible identity link, never the now-hidden trigger.
    expect(document.activeElement?.id).toBe('site-header-home');
    expect(document.activeElement).not.toBe(menuTrigger);
    expectClean();

    // Indexed Detail re-promotes to tabs, remembering the last selection
    // (tab index 2, selected before the narrow crossing).
    const tabsAgain = screen.getAllByRole('tab');
    expect(tabsAgain).toHaveLength(3);
    expect(tabsAgain[2]).toHaveAttribute('aria-selected', 'true');
    expectClean();

    await unmountScenario(root);
    expectClean();
  });
});

// ==========================================================================
// Lighter theme/locale smoke passes — each still hydrates the real composed
// tree with a distinct locale/theme/breakpoint combination and asserts zero
// diagnostics through a representative interaction, without repeating the
// full narrative above.
// ==========================================================================

describe.each([
  ['es', 'dark', false] as const,
  ['en', 'light', false] as const,
  ['en', 'dark', true] as const,
])(
  'hydration — smoke pass (%s, %s theme, desktop=%s)',
  (locale, theme, desktop) => {
    it('hydrates cleanly and survives a theme toggle + a tab click + a dialog open/close', async () => {
      installMatchMedia(desktop);
      const t = labelsFor(locale);

      const root = await mountScenario(locale, theme);
      expectClean();

      expect(document.documentElement.getAttribute('lang')).toBe(locale);
      expect(document.documentElement.dataset.theme).toBe(theme);

      const themeSection = screen.getByRole('region', {
        name: t.theme.label,
      }) as HTMLElement;
      const otherThemeLabel = theme === 'light' ? t.theme.dark : t.theme.light;
      const toggle = within(themeSection).getByRole('button', {
        name: otherThemeLabel,
      });
      fireEvent.click(toggle);
      expect(document.documentElement.dataset.theme).toBe(
        theme === 'light' ? 'dark' : 'light',
      );
      expectClean();

      if (desktop) {
        const tabs = screen.getAllByRole('tab');
        expect(tabs).toHaveLength(3);
        fireEvent.click(tabs[1]);
        expect(tabs[1]).toHaveAttribute('aria-selected', 'true');
        expectClean();
      } else {
        expect(screen.queryByRole('tablist')).toBeNull();
        for (const workCase of WORK_CASES) {
          expect(screen.getByText(workCase.title)).toBeInTheDocument();
        }
        expectClean();
      }

      const menuTrigger = screen.getByRole('button', {name: t.menu.open});
      fireEvent.click(menuTrigger);
      expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(true);
      expectClean();

      const dialog = document.querySelector('dialog') as HTMLElement;
      fireEvent.click(within(dialog).getByRole('button', {name: t.menu.close}));
      expect(document.querySelector('dialog')?.hasAttribute('open')).toBe(
        false,
      );
      expectClean();

      await unmountScenario(root);
      expectClean();
    });
  },
);

// ==========================================================================
// Unsupported-locale hydration boundary: `notFound()` fires before hydration,
// no partial tree is ever handed to `hydrateRoot`.
// ==========================================================================

describe('hydration — unsupported locale never reaches hydrateRoot', () => {
  it('rejects before any server render or client mount is attempted', async () => {
    await expect(
      LocalePage({params: Promise.resolve({locale: 'fr'})}),
    ).rejects.toThrow('NEXT_NOT_FOUND');

    expect(loadPublicPortfolio).not.toHaveBeenCalled();
    expectClean();
  });
});
