import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, render, screen, within} from '@testing-library/react';
import type {
  EndpointResult,
  SiteConfiguration,
  Technology,
} from '@/lib/api/types';
import {ExpertiseGroup, type ExpertiseGroupLabels} from './expertise-group';

/**
 * jsdom cannot prove real visual hiding of the group <h2>; these assertions
 * cover the DOM / ARIA / anchor / grouping contract only (spec §22, §16, §20,
 * §11). Task 15 QA owns real-browser behaviour.
 *
 * Every fixture value below is synthetic and non-professional. The technology
 * group labels ("QA Backend" …) are deliberately NOT the canonical UI category
 * words, so a test can prove the frontend never injects a derived label.
 */

vi.mock('next/navigation', () => ({
  useRouter: () => ({refresh: vi.fn()}),
}));

const LABELS: ExpertiseGroupLabels = {
  group: 'Expertise',
  specialties: 'Areas of specialization',
  technologies: 'Technologies',
  neutralEmpty: 'Content is not available at the moment.',
  regionalFailure: "We couldn't load this section.",
};

const RETRY = {label: 'Retry', pendingLabel: 'Retrying…'} as const;

/** The four canonical groups, canonical order, with non-canonical labels. */
const GROUPS: SiteConfiguration['technology_groups'] = [
  {key: 'backend', label: 'QA Backend'},
  {key: 'data', label: 'QA Data'},
  {key: 'integration', label: 'QA Integration'},
  {key: 'collaboration', label: 'QA Collaboration'},
];

const GROUP_LABELS = GROUPS.map((group) => group.label);

type IconArg = Technology['icon'];

function makeTech(
  key: string,
  category: Technology['category'],
  icon: IconArg = null,
): Technology {
  return {key, name: `Synthetic ${key} tool`, category, icon};
}

function makeArea(
  key: string,
  description: string | null,
): SiteConfiguration['expertise_areas'][number] {
  return {key, title: `Synthetic ${key} specialty`, description};
}

const okTech = (data: Technology[]): EndpointResult<Technology[]> => ({
  ok: true,
  data,
});

const failedTech = (
  kind: 'network' | 'http' | 'malformed' | 'configuration' = 'network',
): EndpointResult<Technology[]> => ({
  ok: false,
  failure: {endpoint: 'technologies', kind},
});

function renderGroup(
  areas: SiteConfiguration['expertise_areas'],
  technologies: EndpointResult<Technology[]>,
  groups: SiteConfiguration['technology_groups'] = GROUPS,
) {
  return render(
    <ExpertiseGroup
      areas={areas}
      groups={groups}
      technologies={technologies}
      labels={LABELS}
      retry={RETRY}
    />,
  );
}

function technologiesRegion(container: HTMLElement): HTMLElement {
  return container.querySelector('section#technologies') as HTMLElement;
}

afterEach(cleanup);

describe('ExpertiseGroup — structural anchor', () => {
  it('always renders #expertise with a visually hidden group h2 named by the nav label', () => {
    const {container} = renderGroup(
      [makeArea('one', 'A synthetic description.')],
      okTech([makeTech('svc', 'backend')]),
    );

    const expertise = container.querySelector('section#expertise');
    expect(expertise).not.toBeNull();
    expect(expertise).toHaveAttribute('aria-labelledby', 'expertise-heading');

    const heading = container.querySelector('#expertise-heading');
    expect(heading?.tagName).toBe('H2');
    expect(heading).toHaveTextContent('Expertise');
    expect(heading).toHaveClass('visually-hidden');

    expect(screen.getByRole('region', {name: 'Expertise'})).toBeInTheDocument();
  });

  it('keeps #expertise present for every branch of the matrix', () => {
    const matrix: Array<
      [SiteConfiguration['expertise_areas'], EndpointResult<Technology[]>]
    > = [
      [[makeArea('a', null)], okTech([makeTech('svc', 'backend')])],
      [[], okTech([makeTech('svc', 'backend')])],
      [[makeArea('a', null)], okTech([])],
      [[], okTech([])],
      [[], failedTech()],
      [[makeArea('a', null)], failedTech()],
    ];

    for (const [areas, tech] of matrix) {
      const {container, unmount} = renderGroup(areas, tech);
      expect(container.querySelector('section#expertise')).not.toBeNull();
      unmount();
    }
  });
});

describe('ExpertiseGroup — specialties + technologies present', () => {
  it('renders Specialties before Technologies in DOM order', () => {
    const {container} = renderGroup(
      [makeArea('one', 'Described.')],
      okTech([makeTech('svc', 'backend')]),
    );

    const html = container.innerHTML;
    expect(html.indexOf('id="specialties"')).toBeGreaterThan(-1);
    expect(html.indexOf('id="technologies"')).toBeGreaterThan(-1);
    expect(html.indexOf('id="specialties"')).toBeLessThan(
      html.indexOf('id="technologies"'),
    );
  });

  it('renders the granular specialties section with its h3 and each area in API order', () => {
    const areas = [
      makeArea('first', 'First description.'),
      makeArea('second', null),
      makeArea('third', 'Third description.'),
    ];
    const {container} = renderGroup(
      areas,
      okTech([makeTech('svc', 'backend')]),
    );

    const region = container.querySelector(
      'section#specialties',
    ) as HTMLElement;
    expect(region).not.toBeNull();
    expect(region).toHaveAttribute('aria-labelledby', 'specialties-heading');
    expect(
      within(region).getByRole('heading', {
        level: 3,
        name: 'Areas of specialization',
      }),
    ).toHaveAttribute('id', 'specialties-heading');

    const titles = within(region)
      .getAllByRole('heading', {level: 4})
      .map((node) => node.textContent);
    expect(titles).toEqual([
      'Synthetic first specialty',
      'Synthetic second specialty',
      'Synthetic third specialty',
    ]);
  });

  it('renders every group h4 label from Site in canonical order regardless of endpoint order', () => {
    const {container} = renderGroup(
      [makeArea('one', 'Described.')],
      // Deliberately scrambled category order in the endpoint payload.
      okTech([
        makeTech('collab-a', 'collaboration'),
        makeTech('backend-a', 'backend'),
        makeTech('data-a', 'data'),
        makeTech('integration-a', 'integration'),
      ]),
    );

    const region = technologiesRegion(container);
    expect(region).not.toBeNull();
    expect(
      within(region).getByRole('heading', {level: 3, name: 'Technologies'}),
    ).toHaveAttribute('id', 'technologies-heading');

    const labels = within(region)
      .getAllByRole('heading', {level: 4})
      .map((node) => node.textContent);
    expect(labels).toEqual(GROUP_LABELS);
  });

  it('preserves the endpoint order of technologies within a single group', () => {
    const {container} = renderGroup(
      [],
      okTech([
        makeTech('zebra', 'backend'),
        makeTech('alpha', 'backend'),
        makeTech('mike', 'backend'),
      ]),
    );

    const names = within(technologiesRegion(container))
      .getAllByRole('listitem')
      .map((node) => node.textContent);
    expect(names).toEqual([
      'Synthetic zebra tool',
      'Synthetic alpha tool',
      'Synthetic mike tool',
    ]);
  });

  it('uses the Site group label verbatim and never injects a derived category word', () => {
    const {container} = renderGroup([], okTech([makeTech('svc', 'backend')]));

    const region = technologiesRegion(container);
    expect(within(region).getByText('QA Backend')).toBeInTheDocument();
    // No standalone canonical category word was invented.
    expect(within(region).queryByText('Backend', {exact: true})).toBeNull();
    expect(within(region).queryByText('backend', {exact: true})).toBeNull();
  });
});

describe('ExpertiseGroup — nullable fields', () => {
  it('omits the description element entirely for an area whose description is null', () => {
    const {container} = renderGroup(
      [makeArea('bare', null), makeArea('full', 'Present description.')],
      okTech([]),
    );

    const region = container.querySelector(
      'section#specialties',
    ) as HTMLElement;
    const items = within(region).getAllByRole('heading', {level: 4});
    expect(items).toHaveLength(2);
    expect(region.querySelectorAll('p')).toHaveLength(1);
    expect(
      within(region).getByText('Present description.'),
    ).toBeInTheDocument();
    // No placeholder text stands in for the missing description.
    expect(region.textContent).not.toContain('null');
  });

  it('keeps the technology name text when its icon is null and when it is present', () => {
    const {container} = renderGroup(
      [],
      okTech([
        makeTech('plain', 'backend', null),
        makeTech('iconed', 'backend', {url: '/storage/qa/icon.svg'}),
      ]),
    );

    const region = technologiesRegion(container);
    expect(
      within(region).getByText('Synthetic plain tool'),
    ).toBeInTheDocument();
    expect(
      within(region).getByText('Synthetic iconed tool'),
    ).toBeInTheDocument();
    // Exactly one decorative image, and it is hidden from assistive tech.
    const images = region.querySelectorAll('img');
    expect(images).toHaveLength(1);
    expect(images[0]).toHaveAttribute('alt', '');
    expect(images[0]).toHaveAttribute('aria-hidden', 'true');
  });
});

describe('ExpertiseGroup — group omission', () => {
  it('omits a group with zero matching technologies while rendering the others', () => {
    const {container} = renderGroup(
      [],
      okTech([makeTech('be', 'backend'), makeTech('int', 'integration')]),
    );

    const labels = within(technologiesRegion(container))
      .getAllByRole('heading', {level: 4})
      .map((node) => node.textContent);
    expect(labels).toEqual(['QA Backend', 'QA Integration']);
  });
});

describe('ExpertiseGroup — successful empty subsections', () => {
  it('omits #specialties entirely when areas is an empty array', () => {
    const {container} = renderGroup([], okTech([makeTech('svc', 'backend')]));

    expect(container.querySelector('section#specialties')).toBeNull();
    expect(container.querySelector('#specialties-heading')).toBeNull();
    expect(technologiesRegion(container)).not.toBeNull();
    expect(screen.queryByText(LABELS.neutralEmpty)).not.toBeInTheDocument();
  });

  it('omits #technologies entirely when the endpoint is a successful empty array', () => {
    const {container} = renderGroup([makeArea('a', null)], okTech([]));

    expect(container.querySelector('section#technologies')).toBeNull();
    expect(container.querySelector('#technologies-heading')).toBeNull();
    expect(container.querySelector('section#specialties')).not.toBeNull();
    expect(screen.queryByText(LABELS.neutralEmpty)).not.toBeInTheDocument();
  });

  it('shows neutral copy under #expertise with no granular anchors when both are empty', () => {
    const {container} = renderGroup([], okTech([]));

    expect(container.querySelector('section#expertise')).not.toBeNull();
    expect(screen.getByText(LABELS.neutralEmpty)).toBeInTheDocument();
    expect(container.querySelector('section#specialties')).toBeNull();
    expect(container.querySelector('section#technologies')).toBeNull();
    expect(container.querySelector('#specialties-heading')).toBeNull();
    expect(container.querySelector('#technologies-heading')).toBeNull();
    expect(
      screen.queryByRole('button', {name: 'Retry'}),
    ).not.toBeInTheDocument();
  });
});

describe('ExpertiseGroup — regional failure', () => {
  it('keeps #technologies with its anchor, h3 and Retry on failure, never neutral copy', () => {
    const {container} = renderGroup([makeArea('a', null)], failedTech());

    const region = technologiesRegion(container);
    expect(region).not.toBeNull();
    expect(region).toHaveAttribute('aria-labelledby', 'technologies-heading');
    expect(
      within(region).getByRole('heading', {level: 3, name: 'Technologies'}),
    ).toBeInTheDocument();
    expect(
      within(region).getByText(LABELS.regionalFailure),
    ).toBeInTheDocument();
    expect(
      within(region).getByRole('button', {name: 'Retry'}),
    ).toBeInTheDocument();
    expect(region.textContent).not.toContain(LABELS.neutralEmpty);
    // The healthy sibling still renders.
    expect(container.querySelector('section#specialties')).not.toBeNull();
  });

  it('treats a malformed technologies result as a regional failure, not neutral empty', () => {
    const {container} = renderGroup([], failedTech('malformed'));

    const region = technologiesRegion(container);
    expect(region).not.toBeNull();
    expect(
      within(region).getByRole('heading', {level: 3, name: 'Technologies'}),
    ).toBeInTheDocument();
    expect(
      within(region).getByRole('button', {name: 'Retry'}),
    ).toBeInTheDocument();
    // areas is [] but a failed sibling means the group is not "both empty".
    expect(screen.queryByText(LABELS.neutralEmpty)).not.toBeInTheDocument();
    expect(container.querySelector('section#specialties')).toBeNull();
  });

  it('renders only #technologies (failure) when areas is a successful empty array', () => {
    const {container} = renderGroup([], failedTech());

    expect(container.querySelector('section#specialties')).toBeNull();
    expect(technologiesRegion(container)).not.toBeNull();
    expect(screen.getByRole('button', {name: 'Retry'})).toBeInTheDocument();
    expect(screen.queryByText(LABELS.neutralEmpty)).not.toBeInTheDocument();
  });
});
