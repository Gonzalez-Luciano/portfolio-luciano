import {afterEach, describe, expect, it, vi} from 'vitest';
import {renderToStaticMarkup} from 'react-dom/server';
import {act, cleanup, fireEvent, render, screen} from '@testing-library/react';
import type {WorkCase} from '@/lib/api/types';
import {IndexedWorkCases} from './indexed-work-cases';
import {WorkCaseDossier, type WorkCaseFieldLabels} from './work-cases-section';

/**
 * jsdom limits: it cannot prove real visual hiding (CSS `display`), real Tab
 * focus order, native `<button>` behavior, or the actual 64rem media query.
 * These assertions cover the DOM / ARIA / state contract only; Task 15 QA owns
 * the real-browser behavior.
 */

const FIELD_LABELS: WorkCaseFieldLabels = {
  context: 'Context',
  problem: 'Problem',
  contribution: 'Contribution',
  technicalApproach: 'Technical approach',
  outcome: 'Outcome',
};

function makeCase(n: number): WorkCase {
  return {
    key: `case-${n}`,
    title: `Synthetic case ${n} title`,
    context: `Synthetic case ${n} context value`,
    problem: `Synthetic case ${n} problem value`,
    contribution: `Synthetic case ${n} contribution value`,
    technical_approach: `Synthetic case ${n} technical-approach value`,
    outcome: `Synthetic case ${n} outcome value`,
    technologies: [],
  };
}

function dossiers(cases: WorkCase[]) {
  return cases.map((workCase) => (
    <WorkCaseDossier
      key={workCase.key}
      workCase={workCase}
      labels={FIELD_LABELS}
    />
  ));
}

type MediaListener = (event: MediaQueryListEvent) => void;

function installMatchMedia(initialMatches: boolean) {
  const listeners = new Set<MediaListener>();
  const mql = {
    matches: initialMatches,
    media: '(min-width: 64rem)',
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

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
});

describe('IndexedWorkCases — SSR completeness before hydration', () => {
  it('renders nothing for zero cases', () => {
    const markup = renderToStaticMarkup(
      <IndexedWorkCases titles={[]}>{dossiers([])}</IndexedWorkCases>,
    );

    expect(markup).toBe('');
  });

  it('renders a single dossier with no tab semantics', () => {
    const only = makeCase(1);
    const markup = renderToStaticMarkup(
      <IndexedWorkCases titles={[only.title]}>
        {dossiers([only])}
      </IndexedWorkCases>,
    );

    expect(markup).toContain(only.title);
    expect(markup).toContain('Technical approach');
    expect(markup).toContain(only.technical_approach);
    expect(markup).not.toContain('role="tab"');
    expect(markup).not.toContain('role="tablist"');
    expect(markup).not.toContain('role="tabpanel"');
  });

  it('renders every case and every required field in Laravel order', () => {
    const cases = [makeCase(1), makeCase(2), makeCase(3)];
    const markup = renderToStaticMarkup(
      <IndexedWorkCases titles={cases.map((entry) => entry.title)}>
        {dossiers(cases)}
      </IndexedWorkCases>,
    );

    for (const entry of cases) {
      expect(markup).toContain(entry.title);
      for (const value of [
        entry.context,
        entry.problem,
        entry.contribution,
        entry.technical_approach,
        entry.outcome,
      ]) {
        expect(markup).toContain(value);
      }
    }

    for (const label of Object.values(FIELD_LABELS)) {
      expect(markup).toContain(label);
    }

    expect(markup.indexOf(cases[0].title)).toBeLessThan(
      markup.indexOf(cases[1].title),
    );
    expect(markup.indexOf(cases[1].title)).toBeLessThan(
      markup.indexOf(cases[2].title),
    );

    const first = cases[0];
    expect(markup.indexOf(first.context)).toBeLessThan(
      markup.indexOf(first.problem),
    );
    expect(markup.indexOf(first.problem)).toBeLessThan(
      markup.indexOf(first.contribution),
    );
    expect(markup.indexOf(first.contribution)).toBeLessThan(
      markup.indexOf(first.technical_approach),
    );
    expect(markup.indexOf(first.technical_approach)).toBeLessThan(
      markup.indexOf(first.outcome),
    );

    expect(markup).not.toContain('role="tab"');
  });
});

describe('IndexedWorkCases — desktop tab enhancement', () => {
  function renderDesktop(count: number) {
    const media = installMatchMedia(true);
    const cases = Array.from({length: count}, (_, index) =>
      makeCase(index + 1),
    );
    const utils = render(
      <IndexedWorkCases titles={cases.map((entry) => entry.title)}>
        {dossiers(cases)}
      </IndexedWorkCases>,
    );

    return {cases, media, ...utils};
  }

  it('promotes multiple cases to a vertical tablist wired by ARIA', () => {
    renderDesktop(3);

    const tablist = screen.getByRole('tablist');
    expect(tablist).toHaveAttribute('aria-orientation', 'vertical');

    const tabs = screen.getAllByRole('tab');
    expect(tabs).toHaveLength(3);

    const panels = screen.getAllByRole('tabpanel', {hidden: true});
    expect(panels).toHaveLength(3);

    tabs.forEach((tab, index) => {
      const panel = panels[index];
      expect(tab).toHaveAttribute('id');
      expect(panel).toHaveAttribute('id');
      expect(tab.getAttribute('aria-controls')).toBe(panel.getAttribute('id'));
      expect(panel.getAttribute('aria-labelledby')).toBe(
        tab.getAttribute('id'),
      );
      expect(panel).toHaveAttribute('tabindex', '0');
    });

    expect(tabs[0]).toHaveAttribute('aria-selected', 'true');
    expect(tabs[1]).toHaveAttribute('aria-selected', 'false');
    expect(tabs[2]).toHaveAttribute('aria-selected', 'false');

    expect(tabs[0]).toHaveAttribute('tabindex', '0');
    expect(tabs[1]).toHaveAttribute('tabindex', '-1');
    expect(tabs[2]).toHaveAttribute('tabindex', '-1');

    expect(panels[0].hasAttribute('hidden')).toBe(false);
    expect(panels[1].hasAttribute('hidden')).toBe(true);
    expect(panels[2].hasAttribute('hidden')).toBe(true);
  });

  it('moves focus and activates the next/previous tab with ArrowDown/ArrowUp', () => {
    renderDesktop(3);
    const tabs = screen.getAllByRole('tab');

    tabs[0].focus();
    fireEvent.keyDown(tabs[0], {key: 'ArrowDown'});

    expect(tabs[1]).toHaveAttribute('aria-selected', 'true');
    expect(document.activeElement).toBe(tabs[1]);
    expect(tabs[1]).toHaveAttribute('tabindex', '0');
    expect(tabs[0]).toHaveAttribute('tabindex', '-1');

    fireEvent.keyDown(tabs[1], {key: 'ArrowUp'});

    expect(tabs[0]).toHaveAttribute('aria-selected', 'true');
    expect(document.activeElement).toBe(tabs[0]);
  });

  it('moves focus to the first/last tab and activates it with Home/End', () => {
    renderDesktop(4);
    const tabs = screen.getAllByRole('tab');

    tabs[0].focus();
    fireEvent.keyDown(tabs[0], {key: 'End'});

    expect(tabs[3]).toHaveAttribute('aria-selected', 'true');
    expect(document.activeElement).toBe(tabs[3]);

    fireEvent.keyDown(tabs[3], {key: 'Home'});

    expect(tabs[0]).toHaveAttribute('aria-selected', 'true');
    expect(document.activeElement).toBe(tabs[0]);
  });

  it('clamps at the ends rather than wrapping', () => {
    renderDesktop(3);
    const tabs = screen.getAllByRole('tab');

    tabs[0].focus();
    fireEvent.keyDown(tabs[0], {key: 'ArrowUp'});
    expect(tabs[0]).toHaveAttribute('aria-selected', 'true');
    expect(document.activeElement).toBe(tabs[0]);

    fireEvent.keyDown(tabs[0], {key: 'End'});
    fireEvent.keyDown(tabs[2], {key: 'ArrowDown'});
    expect(tabs[2]).toHaveAttribute('aria-selected', 'true');
    expect(document.activeElement).toBe(tabs[2]);
  });

  it('does not require Enter or Space for activation', () => {
    renderDesktop(3);
    const tabs = screen.getAllByRole('tab');

    tabs[0].focus();
    fireEvent.keyDown(tabs[0], {key: 'Enter'});
    fireEvent.keyDown(tabs[0], {key: ' '});
    expect(tabs[0]).toHaveAttribute('aria-selected', 'true');

    fireEvent.keyDown(tabs[0], {key: 'ArrowDown'});
    expect(tabs[1]).toHaveAttribute('aria-selected', 'true');
  });

  it('activates a tab and reveals only its panel on click', () => {
    renderDesktop(3);
    const tabs = screen.getAllByRole('tab');

    fireEvent.click(tabs[2]);

    expect(tabs[2]).toHaveAttribute('aria-selected', 'true');
    const panels = screen.getAllByRole('tabpanel', {hidden: true});
    expect(panels[2].hasAttribute('hidden')).toBe(false);
    expect(panels[0].hasAttribute('hidden')).toBe(true);
    expect(panels[1].hasAttribute('hidden')).toBe(true);
  });
});

describe('IndexedWorkCases — local 64rem reconciliation', () => {
  it('keeps every dossier sequential with no roles below 64rem', () => {
    installMatchMedia(false);
    const cases = [makeCase(1), makeCase(2), makeCase(3)];

    render(
      <IndexedWorkCases titles={cases.map((entry) => entry.title)}>
        {dossiers(cases)}
      </IndexedWorkCases>,
    );

    expect(screen.queryByRole('tablist')).toBeNull();
    expect(screen.queryAllByRole('tab')).toHaveLength(0);
    expect(screen.queryAllByRole('tabpanel', {hidden: true})).toHaveLength(0);

    for (const entry of cases) {
      expect(screen.getByText(entry.title)).toBeInTheDocument();
      expect(screen.getByText(entry.outcome)).toBeInTheDocument();
    }
  });

  it('removes tab semantics on crossing below 64rem and restores them with the remembered index on crossing back', () => {
    const media = installMatchMedia(true);
    const cases = [makeCase(1), makeCase(2), makeCase(3)];

    render(
      <IndexedWorkCases titles={cases.map((entry) => entry.title)}>
        {dossiers(cases)}
      </IndexedWorkCases>,
    );

    fireEvent.click(screen.getAllByRole('tab')[1]);
    expect(screen.getAllByRole('tab')[1]).toHaveAttribute(
      'aria-selected',
      'true',
    );

    media.emit(false);
    expect(screen.queryByRole('tablist')).toBeNull();
    for (const entry of cases) {
      expect(screen.getByText(entry.outcome)).toBeInTheDocument();
    }

    media.emit(true);
    const tabsAgain = screen.getAllByRole('tab');
    expect(tabsAgain[1]).toHaveAttribute('aria-selected', 'true');
  });

  it('never applies tab semantics to a single case even on desktop', () => {
    installMatchMedia(true);
    const only = makeCase(1);

    render(
      <IndexedWorkCases titles={[only.title]}>
        {dossiers([only])}
      </IndexedWorkCases>,
    );

    expect(screen.queryByRole('tab')).toBeNull();
    expect(screen.queryByRole('tablist')).toBeNull();
    expect(screen.getByText(only.title)).toBeInTheDocument();
  });
});
