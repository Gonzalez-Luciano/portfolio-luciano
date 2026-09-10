import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, render, screen, within} from '@testing-library/react';
import type {
  EndpointResult,
  Project,
  SiteConfiguration,
} from '@/lib/api/types';
import {ProjectsSection, type ProjectsSectionLabels} from './projects-section';
import {ApproachSection, type ApproachSectionLabels} from './approach-section';
import {ContactSection, type ContactSectionLabels} from './contact-section';

/**
 * Site-owned sections (design spec §16 Projects exception, §22, §23, §20, §8).
 *
 * Every fixture value below is synthetic and non-professional. No real name,
 * URL, CV path, or professional claim appears here. jsdom cannot prove real
 * visual layout or new-tab behaviour; these assertions cover the DOM / ARIA /
 * anchor / attribute contract only. Task 15 QA owns real-browser behaviour.
 */

vi.mock('next/navigation', () => ({
  useRouter: () => ({refresh: vi.fn()}),
}));

afterEach(cleanup);

/* ==========================================================================
   Projects
   ========================================================================== */

const PROJECT_LABELS: ProjectsSectionLabels = {
  sectionTitle: 'Projects',
  problem: 'Problem',
  solution: 'Solution',
  regionalFailure: "We couldn't load this section.",
};

const RETRY = {label: 'Retry', pendingLabel: 'Retrying…'} as const;
const NEUTRAL_EMPTY = 'Content is not available at the moment.';
const PROJECTS_EMPTY_MESSAGE = 'Synthetic zero-project message from the CMS.';

function makeProject(overrides: Partial<Project> = {}): Project {
  return {
    key: 'synthetic-a',
    title: 'Synthetic Project A',
    summary: 'Synthetic summary for project A.',
    problem: 'Synthetic problem for project A.',
    solution: 'Synthetic solution for project A.',
    featured: false,
    image: null,
    demo_url: null,
    repository_url: null,
    technologies: [],
    ...overrides,
  };
}

const okProjects = (data: Project[]): EndpointResult<Project[]> => ({
  ok: true,
  data,
});

const failProjects = (
  kind: 'network' | 'http' | 'malformed' | 'configuration' = 'network',
): EndpointResult<Project[]> => ({
  ok: false,
  failure: {endpoint: 'projects', kind},
});

function renderProjects(projects: EndpointResult<Project[]>) {
  return render(
    <ProjectsSection
      projects={projects}
      emptyMessage={PROJECTS_EMPTY_MESSAGE}
      labels={PROJECT_LABELS}
      retry={RETRY}
    />,
  );
}

function projectsRegion(container: HTMLElement): HTMLElement {
  return container.querySelector('section#projects') as HTMLElement;
}

describe('ProjectsSection — structural anchor', () => {
  it('always renders #projects with a visible h2 across content, empty and failure', () => {
    const branches: Array<EndpointResult<Project[]>> = [
      okProjects([makeProject()]),
      okProjects([]),
      failProjects(),
      failProjects('malformed'),
    ];

    for (const projects of branches) {
      const {container, unmount} = renderProjects(projects);
      const region = projectsRegion(container);
      expect(region).not.toBeNull();
      expect(region).toHaveAttribute('aria-labelledby', 'projects-heading');
      const heading = within(region).getByRole('heading', {
        level: 2,
        name: 'Projects',
      });
      expect(heading).toHaveAttribute('id', 'projects-heading');
      unmount();
    }
  });
});

describe('ProjectsSection — content', () => {
  it('renders every project in array order and never reorders a featured project', () => {
    const {container} = renderProjects(
      okProjects([
        makeProject({key: 'a', title: 'Synthetic Project A', featured: false}),
        makeProject({key: 'b', title: 'Synthetic Project B', featured: true}),
        makeProject({key: 'c', title: 'Synthetic Project C', featured: false}),
      ]),
    );

    const region = projectsRegion(container);
    const titles = within(region)
      .getAllByRole('heading', {level: 3})
      .map((node) => node.textContent);
    expect(titles).toEqual([
      'Synthetic Project A',
      'Synthetic Project B',
      'Synthetic Project C',
    ]);

    const articles = region.querySelectorAll('article');
    expect(articles).toHaveLength(3);
    // Only the featured project (still in position 2) carries the modifier.
    expect(articles[0].className).not.toMatch(/featured/);
    expect(articles[1].className).toMatch(/featured/);
    expect(articles[2].className).not.toMatch(/featured/);
  });

  it('renders the summary and the Problem / Solution label+value pairs', () => {
    const {container} = renderProjects(
      okProjects([
        makeProject({
          summary: 'Synthetic summary sentence.',
          problem: 'Synthetic problem sentence.',
          solution: 'Synthetic solution sentence.',
        }),
      ]),
    );

    const region = projectsRegion(container);
    expect(within(region).getByText('Synthetic summary sentence.')).toBeInTheDocument();
    expect(within(region).getByText('Problem')).toBeInTheDocument();
    expect(within(region).getByText('Synthetic problem sentence.')).toBeInTheDocument();
    expect(within(region).getByText('Solution')).toBeInTheDocument();
    expect(within(region).getByText('Synthetic solution sentence.')).toBeInTheDocument();
  });

  it('omits the media frame entirely when image is null', () => {
    const {container} = renderProjects(okProjects([makeProject({image: null})]));
    const region = projectsRegion(container);
    expect(region.querySelector('img')).toBeNull();
    expect(region.querySelector('figure')).toBeNull();
  });

  it('renders next/image with the CMS url and alt verbatim when image is present', () => {
    const {container} = renderProjects(
      okProjects([
        makeProject({
          image: {
            url: '/storage/synthetic-test/cover.png',
            alt: 'Synthetic cover alt text',
          },
        }),
      ]),
    );

    const region = projectsRegion(container);
    const image = within(region).getByRole('img', {
      name: 'Synthetic cover alt text',
    });
    expect(image).toHaveAttribute('alt', 'Synthetic cover alt text');
    // next/image may rewrite src through /_next/image?url=… — the encoded CMS
    // reference must still be present, verbatim, with nothing prepended.
    expect(image.getAttribute('src')).toContain(
      encodeURIComponent('/storage/synthetic-test/cover.png'),
    );
  });

  it('omits the demo link when demo_url is null and renders it when present', () => {
    const {container: withoutDemo} = renderProjects(
      okProjects([makeProject({demo_url: null})]),
    );
    expect(
      within(projectsRegion(withoutDemo)).queryByRole('link', {
        name: /example\.test\/demo/,
      }),
    ).toBeNull();

    const {container: withDemo} = renderProjects(
      okProjects([makeProject({demo_url: 'https://example.test/demo/a'})]),
    );
    const demo = within(projectsRegion(withDemo)).getByRole('link', {
      name: /example\.test\/demo\/a/,
    });
    expect(demo).toHaveAttribute('href', 'https://example.test/demo/a');
    // Same-context navigation: no forced new tab.
    expect(demo).not.toHaveAttribute('target');
  });

  it('omits the repository link when repository_url is null and renders it when present', () => {
    const {container: withoutRepo} = renderProjects(
      okProjects([makeProject({repository_url: null})]),
    );
    expect(
      within(projectsRegion(withoutRepo)).queryByRole('link', {
        name: /example\.test\/repo/,
      }),
    ).toBeNull();

    const {container: withRepo} = renderProjects(
      okProjects([
        makeProject({repository_url: 'https://example.test/repo/a'}),
      ]),
    );
    const repo = within(projectsRegion(withRepo)).getByRole('link', {
      name: /example\.test\/repo\/a/,
    });
    expect(repo).toHaveAttribute('href', 'https://example.test/repo/a');
    expect(repo).not.toHaveAttribute('target');
  });
});

describe('ProjectsSection — empty is the §16 explicit exception', () => {
  it('renders only the CMS empty message inside #projects: no Retry, no neutral copy', () => {
    const {container} = renderProjects(okProjects([]));
    const region = projectsRegion(container);

    expect(within(region).getByText(PROJECTS_EMPTY_MESSAGE)).toBeInTheDocument();
    expect(
      screen.queryByRole('button', {name: 'Retry'}),
    ).not.toBeInTheDocument();
    expect(screen.queryByText(NEUTRAL_EMPTY)).not.toBeInTheDocument();
    expect(
      screen.queryByText(PROJECT_LABELS.regionalFailure),
    ).not.toBeInTheDocument();
    expect(region.querySelector('article')).toBeNull();
  });
});

describe('ProjectsSection — regional failure', () => {
  it('renders regional-failure copy + Retry inside #projects for a generic failure', () => {
    const {container} = renderProjects(failProjects());
    const region = projectsRegion(container);

    expect(
      within(region).getByText(PROJECT_LABELS.regionalFailure),
    ).toBeInTheDocument();
    expect(
      within(region).getByRole('button', {name: 'Retry'}),
    ).toBeInTheDocument();
    expect(within(region).queryByText(PROJECTS_EMPTY_MESSAGE)).toBeNull();
  });

  it('treats a malformed result the same way (regional failure, never empty copy)', () => {
    const {container} = renderProjects(failProjects('malformed'));
    const region = projectsRegion(container);

    expect(
      within(region).getByText(PROJECT_LABELS.regionalFailure),
    ).toBeInTheDocument();
    expect(
      within(region).getByRole('button', {name: 'Retry'}),
    ).toBeInTheDocument();
    expect(within(region).queryByText(PROJECTS_EMPTY_MESSAGE)).toBeNull();
  });
});

/* ==========================================================================
   Approach
   ========================================================================== */

const APPROACH_LABELS: ApproachSectionLabels = {
  sectionTitle: 'Working approach',
  neutralEmpty: NEUTRAL_EMPTY,
};

type Principles = SiteConfiguration['work_principles'];

function renderApproach(principles: Principles) {
  return render(
    <ApproachSection principles={principles} labels={APPROACH_LABELS} />,
  );
}

describe('ApproachSection', () => {
  it('always renders #approach with a visible h2', () => {
    for (const principles of [
      [{key: 'a', statement: 'First principle.'}],
      [] as Principles,
    ]) {
      const {container, unmount} = renderApproach(principles);
      const region = container.querySelector('section#approach') as HTMLElement;
      expect(region).not.toBeNull();
      expect(region).toHaveAttribute('aria-labelledby', 'approach-heading');
      expect(
        within(region).getByRole('heading', {
          level: 2,
          name: 'Working approach',
        }),
      ).toHaveAttribute('id', 'approach-heading');
      unmount();
    }
  });

  it('renders every principle statement in API order under a #work-principles anchor', () => {
    const {container} = renderApproach([
      {key: 'understand', statement: 'Understand the problem first.'},
      {key: 'consistency', statement: 'Keep the codebase consistent.'},
      {key: 'maintainability', statement: 'Favour maintainability.'},
      {key: 'communication', statement: 'Communicate clearly and often.'},
    ]);

    const list = container.querySelector('#work-principles') as HTMLElement;
    expect(list).not.toBeNull();

    const statements = within(list)
      .getAllByRole('listitem')
      .map((node) => node.textContent);
    expect(statements).toEqual([
      'Understand the problem first.',
      'Keep the codebase consistent.',
      'Favour maintainability.',
      'Communicate clearly and often.',
    ]);
    expect(screen.queryByText(NEUTRAL_EMPTY)).not.toBeInTheDocument();
  });

  it('shows neutral copy and omits #work-principles when principles is empty', () => {
    const {container} = renderApproach([]);

    expect(container.querySelector('section#approach')).not.toBeNull();
    expect(screen.getByText(NEUTRAL_EMPTY)).toBeInTheDocument();
    expect(container.querySelector('#work-principles')).toBeNull();
    expect(screen.queryAllByRole('listitem')).toHaveLength(0);
  });
});

/* ==========================================================================
   Contact
   ========================================================================== */

const CONTACT_LABELS: ContactSectionLabels = {
  sectionTitle: 'Contact and CV',
  neutralEmpty: NEUTRAL_EMPTY,
  opensNewTab: 'Opens in a new tab',
};

const CONTACT_LINKS: SiteConfiguration['professional_links'] = [
  {
    key: 'linkedin',
    label: 'Synthetic LinkedIn',
    href: 'https://example.test/in/synthetic-person',
  },
  {
    key: 'github',
    label: 'Synthetic GitHub',
    href: 'https://example.test/synthetic-person',
  },
  {
    key: 'email',
    label: 'Synthetic Email',
    href: 'mailto:synthetic-person@example.test',
  },
];

const CONTACT_CV = {
  url: '/synthetic-route/cv-download',
  label: 'Synthetic CV',
} as const;

function renderContact(props: {
  intro?: string;
  links?: SiteConfiguration['professional_links'];
  cv?: SiteConfiguration['cv'];
}) {
  return render(
    <ContactSection
      intro={props.intro ?? ''}
      links={props.links ?? []}
      cv={props.cv ?? null}
      labels={CONTACT_LABELS}
    />,
  );
}

function contactRegion(container: HTMLElement): HTMLElement {
  return container.querySelector('section#contact') as HTMLElement;
}

describe('ContactSection — validity rule (spec §23 / §16)', () => {
  it('always renders #contact with a visible h2', () => {
    const {container} = renderContact({});
    const region = contactRegion(container);
    expect(region).not.toBeNull();
    expect(region).toHaveAttribute('aria-labelledby', 'contact-heading');
    expect(
      within(region).getByRole('heading', {level: 2, name: 'Contact and CV'}),
    ).toHaveAttribute('id', 'contact-heading');
  });

  it('renders normally with an intro alone (no neutral copy)', () => {
    const {container} = renderContact({intro: 'Synthetic contact intro sentence.'});
    expect(
      within(contactRegion(container)).getByText(
        'Synthetic contact intro sentence.',
      ),
    ).toBeInTheDocument();
    expect(screen.queryByText(NEUTRAL_EMPTY)).not.toBeInTheDocument();
  });

  it('renders normally with links alone (no neutral copy)', () => {
    const {container} = renderContact({links: CONTACT_LINKS});
    expect(screen.queryByText(NEUTRAL_EMPTY)).not.toBeInTheDocument();
    expect(
      within(contactRegion(container)).getAllByRole('link'),
    ).toHaveLength(3);
  });

  it('renders normally with a cv alone (no neutral copy)', () => {
    const {container} = renderContact({cv: CONTACT_CV});
    expect(screen.queryByText(NEUTRAL_EMPTY)).not.toBeInTheDocument();
    expect(
      within(contactRegion(container)).getByRole('link', {name: 'Synthetic CV'}),
    ).toBeInTheDocument();
  });

  it('shows neutral copy only when intro is empty/whitespace AND links [] AND cv null', () => {
    const {container: blank} = renderContact({intro: '', links: [], cv: null});
    expect(screen.getByText(NEUTRAL_EMPTY)).toBeInTheDocument();
    expect(within(contactRegion(blank)).queryAllByRole('link')).toHaveLength(0);

    cleanup();

    const {container: whitespace} = renderContact({
      intro: '   \n  ',
      links: [],
      cv: null,
    });
    expect(screen.getByText(NEUTRAL_EMPTY)).toBeInTheDocument();
    // The whitespace-only intro produces no prose paragraph of its own.
    expect(
      contactRegion(whitespace).querySelectorAll('.contact__intro'),
    ).toHaveLength(0);
  });

  it('renders no CV action when cv is null', () => {
    const {container} = renderContact({
      intro: 'Synthetic contact intro sentence.',
      links: CONTACT_LINKS,
      cv: null,
    });
    expect(
      within(contactRegion(container)).queryByRole('link', {name: 'Synthetic CV'}),
    ).toBeNull();
  });
});

describe('ContactSection — link new-tab matrix (spec §23)', () => {
  it('opens LinkedIn and GitHub in a new tab with safe rel and a hidden announcement', () => {
    const {container} = renderContact({links: CONTACT_LINKS, cv: CONTACT_CV});
    const region = contactRegion(container);

    for (const label of ['Synthetic LinkedIn', 'Synthetic GitHub']) {
      const link = within(region).getByRole('link', {
        name: new RegExp(label),
      });
      expect(link).toHaveAttribute('target', '_blank');
      expect(link).toHaveAttribute('rel', 'me noopener noreferrer');
      const hidden = link.querySelector('.visually-hidden');
      expect(hidden).not.toBeNull();
      expect(hidden).toHaveTextContent('Opens in a new tab');
      // The visible label is untouched.
      expect(link.textContent).toContain(label);
    }
  });

  it('keeps the email link and the CV anchor as normal same-context anchors', () => {
    const {container} = renderContact({links: CONTACT_LINKS, cv: CONTACT_CV});
    const region = contactRegion(container);

    const email = within(region).getByRole('link', {name: 'Synthetic Email'});
    expect(email).toHaveAttribute('href', 'mailto:synthetic-person@example.test');
    expect(email).not.toHaveAttribute('target');
    expect(email.querySelector('.visually-hidden')).toBeNull();

    const cv = within(region).getByRole('link', {name: 'Synthetic CV'});
    expect(cv).not.toHaveAttribute('target');
    expect(cv.querySelector('.visually-hidden')).toBeNull();
  });

  it('renders links in array order and consumes cv.url verbatim as the CV href', () => {
    const {container} = renderContact({links: CONTACT_LINKS, cv: CONTACT_CV});
    const hrefs = within(contactRegion(container))
      .getAllByRole('link')
      .map((node) => node.getAttribute('href'));
    expect(hrefs).toEqual([
      'https://example.test/in/synthetic-person',
      'https://example.test/synthetic-person',
      'mailto:synthetic-person@example.test',
      '/synthetic-route/cv-download',
    ]);
  });
});

describe('ContactSection — no hardcoded CV path in source', () => {
  it('contains no /cv/luciano-gonzalez- literal', () => {
    const source = readFileSync(
      resolve(process.cwd(), 'src/components/sections/contact-section.tsx'),
      'utf8',
    );
    expect(source).not.toMatch(/\/cv\/luciano-gonzalez-/);
    expect(source).not.toMatch(/luciano-gonzalez-.*\.pdf/i);
  });
});
