import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, render, screen, within} from '@testing-library/react';
import type {Experience, EndpointResult, WorkCase} from '@/lib/api/types';
import {WorkGroup, type WorkGroupLabels} from './work-group';

/**
 * jsdom cannot prove real visual hiding of the group <h2>; these assertions
 * cover the DOM / ARIA / anchor contract only (spec §16, §20, §15.2). Task 15
 * QA owns the real-browser behaviour.
 */

vi.mock('next/navigation', () => ({
  useRouter: () => ({refresh: vi.fn()}),
}));

const LABELS: WorkGroupLabels = {
  group: 'Work',
  workCases: 'Work cases',
  experience: 'Experience',
  fields: {
    context: 'Context',
    problem: 'Problem',
    contribution: 'Contribution',
    technicalApproach: 'Technical approach',
    outcome: 'Outcome',
  },
  currentExperienceEnd: 'Present',
  neutralEmpty: 'Content is not available at the moment.',
  regionalFailure: "We couldn't load this section.",
};

const RETRY = {label: 'Retry', pendingLabel: 'Retrying…'} as const;

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

function makeExperience(overrides: Partial<Experience> = {}): Experience {
  return {
    key: 'exp-1',
    organization: 'Synthetic Organization',
    role: 'Synthetic Role',
    start: '2021-03',
    end: '2023-07',
    summary: 'Synthetic experience summary sentence.',
    highlights: ['Synthetic highlight one', 'Synthetic highlight two'],
    technologies: [],
    ...overrides,
  };
}

const okCases = (cases: WorkCase[]): EndpointResult<WorkCase[]> => ({
  ok: true,
  data: cases,
});
const okExperiences = (
  experiences: Experience[],
): EndpointResult<Experience[]> => ({ok: true, data: experiences});
const failed = <T,>(
  endpoint: 'work-cases' | 'experiences',
  kind: 'network' | 'http' | 'malformed' | 'configuration' = 'network',
): EndpointResult<T> => ({ok: false, failure: {endpoint, kind}});

function renderGroup(
  workCases: EndpointResult<WorkCase[]>,
  experiences: EndpointResult<Experience[]>,
  locale = 'en',
) {
  return render(
    <WorkGroup
      workCases={workCases}
      experiences={experiences}
      locale={locale}
      labels={LABELS}
      retry={RETRY}
    />,
  );
}

afterEach(cleanup);

describe('WorkGroup — structural anchor', () => {
  it('always renders #work with a visually hidden group h2 named by the nav label', () => {
    const {container} = renderGroup(okCases([makeCase(1)]), okExperiences([]));

    const work = container.querySelector('section#work');
    expect(work).not.toBeNull();
    expect(work).toHaveAttribute('aria-labelledby', 'work-heading');

    const heading = container.querySelector('#work-heading');
    expect(heading?.tagName).toBe('H2');
    expect(heading).toHaveTextContent('Work');
    expect(heading).toHaveClass('visually-hidden');

    expect(
      screen.getByRole('region', {name: 'Work'}),
    ).toBeInTheDocument();
  });

  it('keeps #work present for every branch of the matrix', () => {
    for (const [wc, ex] of [
      [okCases([makeCase(1)]), okExperiences([makeExperience()])],
      [okCases([]), okExperiences([makeExperience()])],
      [okCases([makeCase(1)]), okExperiences([])],
      [okCases([]), okExperiences([])],
      [failed('work-cases'), okExperiences([makeExperience()])],
      [okCases([makeCase(1)]), failed('experiences')],
      [failed('work-cases'), failed('experiences')],
    ] as const) {
      const {container, unmount} = renderGroup(
        wc as EndpointResult<WorkCase[]>,
        ex as EndpointResult<Experience[]>,
      );
      expect(container.querySelector('section#work')).not.toBeNull();
      unmount();
    }
  });
});

describe('WorkGroup — both collections with content', () => {
  it('renders Work Cases before Experience in DOM order', () => {
    const {container} = renderGroup(
      okCases([makeCase(1), makeCase(2)]),
      okExperiences([makeExperience()]),
    );

    const html = container.innerHTML;
    expect(html.indexOf('id="work-cases"')).toBeGreaterThan(-1);
    expect(html.indexOf('id="experience"')).toBeGreaterThan(-1);
    expect(html.indexOf('id="work-cases"')).toBeLessThan(
      html.indexOf('id="experience"'),
    );
  });

  it('renders the granular work-cases section with its h3 and every case field', () => {
    const {container} = renderGroup(
      okCases([makeCase(1), makeCase(2)]),
      okExperiences([makeExperience()]),
    );

    const region = container.querySelector('section#work-cases');
    expect(region).not.toBeNull();
    expect(region).toHaveAttribute('aria-labelledby', 'work-cases-heading');
    const heading = within(region as HTMLElement).getByRole('heading', {
      level: 3,
      name: 'Work cases',
    });
    expect(heading).toHaveAttribute('id', 'work-cases-heading');

    for (const n of [1, 2]) {
      const one = makeCase(n);
      for (const value of [
        one.context,
        one.problem,
        one.contribution,
        one.technical_approach,
        one.outcome,
      ]) {
        expect(screen.getByText(value)).toBeInTheDocument();
      }
    }
    for (const label of Object.values(LABELS.fields)) {
      expect(screen.getAllByText(label).length).toBeGreaterThan(0);
    }
  });

  it('renders the granular experience section with organization, role, localized dates and highlights', () => {
    const {container} = renderGroup(
      okCases([makeCase(1)]),
      okExperiences([makeExperience()]),
      'en',
    );

    const region = container.querySelector('section#experience');
    expect(region).not.toBeNull();
    expect(
      within(region as HTMLElement).getByRole('heading', {
        level: 3,
        name: 'Experience',
      }),
    ).toHaveAttribute('id', 'experience-heading');

    expect(screen.getByText('Synthetic Organization')).toBeInTheDocument();
    expect(screen.getByText('Synthetic Role')).toBeInTheDocument();
    expect(screen.getByText('Synthetic experience summary sentence.')).toBeInTheDocument();
    expect(region).toHaveTextContent('March 2021');
    expect(region).toHaveTextContent('July 2023');
    expect(screen.getByText('Synthetic highlight one')).toBeInTheDocument();
    expect(screen.getByText('Synthetic highlight two')).toBeInTheDocument();
  });

  it('omits organization cleanly when null and renders Present for an open end date', () => {
    const {container} = renderGroup(
      okCases([makeCase(1)]),
      okExperiences([
        makeExperience({
          key: 'exp-open',
          organization: null,
          end: null,
          highlights: [],
        }),
      ]),
      'en',
    );

    const region = container.querySelector('section#experience') as HTMLElement;
    expect(region).toHaveTextContent('Present');
    expect(region.textContent).not.toContain('Synthetic Organization');
    expect(region.querySelector('.experience__highlights')).toBeNull();
  });

  it('localizes the experience dates for the route locale', () => {
    const {container} = renderGroup(
      okCases([makeCase(1)]),
      okExperiences([makeExperience()]),
      'es',
    );

    const region = container.querySelector('section#experience') as HTMLElement;
    expect(region.textContent?.toLowerCase()).toContain('marzo');
    expect(region).toHaveTextContent('2021');
  });
});

describe('WorkGroup — successful empty subsections', () => {
  it('omits the work-cases section entirely when it is a successful empty array', () => {
    const {container} = renderGroup(
      okCases([]),
      okExperiences([makeExperience()]),
    );

    expect(container.querySelector('section#work-cases')).toBeNull();
    expect(container.querySelector('#work-cases-heading')).toBeNull();
    expect(container.querySelector('section#experience')).not.toBeNull();
    expect(
      screen.queryByText(LABELS.neutralEmpty),
    ).not.toBeInTheDocument();
  });

  it('omits the experience section entirely when it is a successful empty array', () => {
    const {container} = renderGroup(
      okCases([makeCase(1)]),
      okExperiences([]),
    );

    expect(container.querySelector('section#experience')).toBeNull();
    expect(container.querySelector('#experience-heading')).toBeNull();
    expect(container.querySelector('section#work-cases')).not.toBeNull();
  });

  it('shows neutral copy under #work with no granular anchors when both are empty', () => {
    const {container} = renderGroup(okCases([]), okExperiences([]));

    expect(container.querySelector('section#work')).not.toBeNull();
    expect(screen.getByText(LABELS.neutralEmpty)).toBeInTheDocument();
    expect(container.querySelector('section#work-cases')).toBeNull();
    expect(container.querySelector('section#experience')).toBeNull();
    expect(container.querySelector('#work-cases-heading')).toBeNull();
    expect(container.querySelector('#experience-heading')).toBeNull();
    // Neutral, not a failure: no Retry control anywhere in the group.
    expect(screen.queryByRole('button', {name: 'Retry'})).not.toBeInTheDocument();
  });
});

describe('WorkGroup — regional failures', () => {
  it('renders a failed work-cases section with its anchor, h3 and Retry, never neutral copy', () => {
    const {container} = renderGroup(
      failed('work-cases'),
      okExperiences([makeExperience()]),
    );

    const region = container.querySelector('section#work-cases') as HTMLElement;
    expect(region).not.toBeNull();
    expect(region).toHaveAttribute('aria-labelledby', 'work-cases-heading');
    expect(
      within(region).getByRole('heading', {level: 3, name: 'Work cases'}),
    ).toBeInTheDocument();
    expect(within(region).getByText(LABELS.regionalFailure)).toBeInTheDocument();
    expect(
      within(region).getByRole('button', {name: 'Retry'}),
    ).toBeInTheDocument();
    expect(region.textContent).not.toContain(LABELS.neutralEmpty);
    // The healthy sibling still renders.
    expect(container.querySelector('section#experience')).not.toBeNull();
  });

  it('renders a failed experience section with its anchor, h3 and Retry', () => {
    const {container} = renderGroup(
      okCases([makeCase(1)]),
      failed('experiences'),
    );

    const region = container.querySelector('section#experience') as HTMLElement;
    expect(region).not.toBeNull();
    expect(
      within(region).getByRole('heading', {level: 3, name: 'Experience'}),
    ).toBeInTheDocument();
    expect(within(region).getByText(LABELS.regionalFailure)).toBeInTheDocument();
    expect(
      within(region).getByRole('button', {name: 'Retry'}),
    ).toBeInTheDocument();
  });

  it('renders both failed sections with Retry and no neutral copy when both fail', () => {
    const {container} = renderGroup(failed('work-cases'), failed('experiences'));

    expect(container.querySelector('section#work-cases')).not.toBeNull();
    expect(container.querySelector('section#experience')).not.toBeNull();
    expect(screen.getAllByRole('button', {name: 'Retry'})).toHaveLength(2);
    expect(screen.queryByText(LABELS.neutralEmpty)).not.toBeInTheDocument();
  });

  it('treats a malformed collection result as a regional failure', () => {
    const {container} = renderGroup(
      failed('work-cases', 'malformed'),
      okExperiences([]),
    );

    const region = container.querySelector('section#work-cases') as HTMLElement;
    expect(region).not.toBeNull();
    expect(
      within(region).getByRole('heading', {level: 3, name: 'Work cases'}),
    ).toBeInTheDocument();
    expect(
      within(region).getByRole('button', {name: 'Retry'}),
    ).toBeInTheDocument();
    // A failed sibling means the group is not "both empty".
    expect(screen.queryByText(LABELS.neutralEmpty)).not.toBeInTheDocument();
  });
});

describe('WorkGroup — mixed empty / non-empty', () => {
  it('renders only the failing section when the other is a successful empty array', () => {
    const {container} = renderGroup(okCases([]), failed('experiences'));

    expect(container.querySelector('section#work-cases')).toBeNull();
    expect(container.querySelector('section#experience')).not.toBeNull();
    expect(screen.getByRole('button', {name: 'Retry'})).toBeInTheDocument();
    expect(screen.queryByText(LABELS.neutralEmpty)).not.toBeInTheDocument();
  });
});
