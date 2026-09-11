import {describe, expect, it} from 'vitest';

import {
  isExperienceList,
  isProfile,
  isProjectList,
  isSiteConfiguration,
  isTechnologyList,
  isWorkCaseList,
} from './validators';
import {
  malformedTechnology,
  technologyGroupsDuplicate,
  technologyGroupsExtraEntry,
  technologyGroupsWrongOrder,
  validExperience,
  validMediaWithAlt,
  validProfile,
  validProject,
  validSite,
  validTechnology,
  validTechnologyWithIcon,
  validWorkCase,
} from '@/test/fixtures/public-api';

type Case = [description: string, payload: unknown];

describe('isProfile', () => {
  it('accepts a valid profile with a null photo', () => {
    expect(isProfile(validProfile)).toBe(true);
  });

  it('accepts a valid profile with a nullable photo populated', () => {
    expect(isProfile({...validProfile, photo: validMediaWithAlt})).toBe(true);
  });

  it('tolerates additional unconsumed backend fields', () => {
    expect(
      isProfile({...validProfile, location: 'Test City', legacy_id: 7}),
    ).toBe(true);
  });

  const rejected: Case[] = [
    ['a non-object payload', 'not-an-object'],
    ['a null payload', null],
    ['an array payload', [validProfile]],
    ['a missing required key', {...withoutKey(validProfile, 'headline')}],
    [
      'an explicit undefined required key',
      {...validProfile, headline: undefined},
    ],
    ['a wrong primitive type', {...validProfile, cta: 42}],
    [
      'a null value in a required string',
      {...validProfile, short_summary: null},
    ],
    [
      'a photo missing its alt text',
      {...validProfile, photo: {url: '/storage/x.jpg'}},
    ],
    [
      'a photo url that is not a root-relative storage path',
      {
        ...validProfile,
        photo: {url: 'https://cdn.example.test/x.jpg', alt: 'x'},
      },
    ],
  ];

  it.each(rejected)('rejects %s', (_description, payload) => {
    expect(isProfile(payload)).toBe(false);
  });
});

describe('isTechnologyList', () => {
  it('accepts an empty collection', () => {
    expect(isTechnologyList([])).toBe(true);
  });

  it('accepts a list with a null icon and a populated icon', () => {
    expect(isTechnologyList([validTechnology, validTechnologyWithIcon])).toBe(
      true,
    );
  });

  it('rejects one malformed item inside a collection', () => {
    expect(
      isTechnologyList([validTechnology, {...validTechnology, name: 3}]),
    ).toBe(false);
  });

  const rejected: Case[] = [
    ['a non-array payload', validTechnology],
    ['a category outside the closed enum', [malformedTechnology]],
    ['a missing category', [withoutKey(validTechnology, 'category')]],
    ['an icon that is a bare string', [{...validTechnology, icon: 'icon.svg'}]],
    [
      'an icon url that is not a storage path',
      [{...validTechnology, icon: {url: 'icon.svg'}}],
    ],
  ];

  it.each(rejected)('rejects %s', (_description, payload) => {
    expect(isTechnologyList(payload)).toBe(false);
  });
});

describe('isExperienceList', () => {
  it('accepts an empty collection', () => {
    expect(isExperienceList([])).toBe(true);
  });

  it('accepts a current experience (end is null) with an organization', () => {
    expect(
      isExperienceList([
        {...validExperience, organization: 'Test Organization', end: null},
      ]),
    ).toBe(true);
  });

  it('accepts a closed experience with a valid YYYY-MM end', () => {
    expect(isExperienceList([{...validExperience, end: '2023-12'}])).toBe(true);
  });

  it('accepts empty highlights and technologies arrays', () => {
    expect(
      isExperienceList([
        {...validExperience, highlights: [], technologies: []},
      ]),
    ).toBe(true);
  });

  const rejected: Case[] = [
    [
      'a missing organization key',
      [withoutKey(validExperience, 'organization')],
    ],
    ['a start month above 12', [{...validExperience, start: '2020-13'}]],
    ['a start month of 00', [{...validExperience, start: '2020-00'}]],
    ['a non-zero-padded start month', [{...validExperience, start: '2020-1'}]],
    ['a free-text start value', [{...validExperience, start: 'January 2020'}]],
    ['a malformed end month', [{...validExperience, end: '2020-99'}]],
    ['a non-string highlight', [{...validExperience, highlights: ['ok', 5]}]],
    [
      'a malformed nested technology',
      [
        {
          ...validExperience,
          technologies: [validTechnology, malformedTechnology],
        },
      ],
    ],
    ['a missing highlights key', [withoutKey(validExperience, 'highlights')]],
    [
      'one malformed item among valid ones',
      [validExperience, {...validExperience, role: 9}],
    ],
  ];

  it.each(rejected)('rejects %s', (_description, payload) => {
    expect(isExperienceList(payload)).toBe(false);
  });
});

describe('isWorkCaseList', () => {
  it('accepts an empty collection', () => {
    expect(isWorkCaseList([])).toBe(true);
  });

  it('accepts a valid work case with an empty technologies array', () => {
    expect(isWorkCaseList([validWorkCase])).toBe(true);
  });

  it('tolerates additional unconsumed fields on an item', () => {
    expect(
      isWorkCaseList([{...validWorkCase, confidentiality_note: 'x'}]),
    ).toBe(true);
  });

  const rejected: Case[] = [
    ['a missing narrative field', [withoutKey(validWorkCase, 'outcome')]],
    ['a null narrative field', [{...validWorkCase, problem: null}]],
    [
      'a wrong primitive narrative field',
      [{...validWorkCase, contribution: 12}],
    ],
    ['a missing technologies key', [withoutKey(validWorkCase, 'technologies')]],
    [
      'a malformed nested technology',
      [{...validWorkCase, technologies: [malformedTechnology]}],
    ],
    [
      'one malformed item among valid ones',
      [validWorkCase, {...validWorkCase, title: 1}],
    ],
  ];

  it.each(rejected)('rejects %s', (_description, payload) => {
    expect(isWorkCaseList(payload)).toBe(false);
  });
});

describe('isProjectList', () => {
  it('accepts an empty collection', () => {
    expect(isProjectList([])).toBe(true);
  });

  it('accepts a project with nullable media and https links populated', () => {
    expect(
      isProjectList([
        {
          ...validProject,
          featured: true,
          image: validMediaWithAlt,
          demo_url: 'https://example.test/demo',
          repository_url: 'https://example.test/repo',
        },
      ]),
    ).toBe(true);
  });

  it('accepts any non-null string url regardless of scheme (verbatim column contract)', () => {
    expect(
      isProjectList([
        {
          ...validProject,
          demo_url: 'http://example.test/demo',
          repository_url: 'example.test/repo',
        },
      ]),
    ).toBe(true);
  });

  it('accepts null demo_url and repository_url', () => {
    expect(
      isProjectList([{...validProject, demo_url: null, repository_url: null}]),
    ).toBe(true);
  });

  const rejected: Case[] = [
    ['a non-boolean featured flag', [{...validProject, featured: 'yes'}]],
    ['a missing solution field', [withoutKey(validProject, 'solution')]],
    [
      'an image missing its alt text',
      [{...validProject, image: {url: '/storage/x.jpg'}}],
    ],
    ['a non-string demo url', [{...validProject, demo_url: 42}]],
    ['a non-string repository url', [{...validProject, repository_url: ['x']}]],
    [
      'a malformed nested technology',
      [{...validProject, technologies: [malformedTechnology]}],
    ],
    [
      'one malformed item among valid ones',
      [validProject, {...validProject, key: 4}],
    ],
  ];

  it.each(rejected)('rejects %s', (_description, payload) => {
    expect(isProjectList(payload)).toBe(false);
  });
});

describe('isSiteConfiguration', () => {
  it('accepts a valid site configuration', () => {
    expect(isSiteConfiguration(validSite)).toBe(true);
  });

  it('accepts a null cv reference', () => {
    expect(isSiteConfiguration({...validSite, cv: null})).toBe(true);
  });

  it('accepts empty professional_links, expertise_areas and work_principles', () => {
    expect(
      isSiteConfiguration({
        ...validSite,
        professional_links: [],
        expertise_areas: [],
        work_principles: [],
      }),
    ).toBe(true);
  });

  it('tolerates additional unconsumed fields', () => {
    expect(isSiteConfiguration({...validSite, meta_title: 'x'})).toBe(true);
  });

  const rejected: Case[] = [
    ['a missing contact_intro', withoutKey(validSite, 'contact_intro')],
    [
      'technology groups in the wrong order',
      {...validSite, technology_groups: technologyGroupsWrongOrder},
    ],
    [
      'technology groups with a duplicate key',
      {...validSite, technology_groups: technologyGroupsDuplicate},
    ],
    [
      'technology groups with a fifth entry',
      {...validSite, technology_groups: technologyGroupsExtraEntry},
    ],
    [
      'technology groups missing a label',
      {
        ...validSite,
        technology_groups: [
          {key: 'backend'},
          {key: 'data', label: 'Test Data'},
          {key: 'integration', label: 'Test Integration'},
          {key: 'collaboration', label: 'Test Collaboration'},
        ],
      },
    ],
    [
      'a professional link with an unknown key',
      {
        ...validSite,
        professional_links: [
          {key: 'website', label: 'Test', href: 'https://example.test'},
        ],
      },
    ],
    [
      'an email professional link that is not a mailto',
      {
        ...validSite,
        professional_links: [
          {key: 'email', label: 'Test', href: 'https://example.test'},
        ],
      },
    ],
    [
      'an https professional link that is a bare host',
      {
        ...validSite,
        professional_links: [
          {key: 'linkedin', label: 'Test', href: 'example.test/in/x'},
        ],
      },
    ],
    [
      'an expertise area missing its title',
      {...validSite, expertise_areas: [{key: 'x', description: null}]},
    ],
    [
      'a work principle missing its statement',
      {...validSite, work_principles: [{key: 'x'}]},
    ],
    [
      'a cv reference whose url is not a /cv/ pdf path',
      {...validSite, cv: {url: '/storage/cv.pdf', label: 'Test CV'}},
    ],
    [
      'a cv reference missing its label',
      {...validSite, cv: {url: '/cv/runtime-test.pdf'}},
    ],
  ];

  it.each(rejected)('rejects %s', (_description, payload) => {
    expect(isSiteConfiguration(payload)).toBe(false);
  });
});

/** Return a shallow copy of `source` with `key` removed. */
function withoutKey(source: object, key: string): Record<string, unknown> {
  return Object.fromEntries(
    Object.entries(source).filter(([entryKey]) => entryKey !== key),
  );
}
