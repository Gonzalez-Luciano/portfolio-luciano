/**
 * Synthetic, test-only fixtures for the six Phase 5 public contracts.
 *
 * Every value here is obviously non-professional ("Test Person",
 * "runtime-test", "example.test"). These fixtures exercise the runtime
 * validators in `src/lib/api/validators.ts`. They must never be imported by
 * production components, and they are unrelated to any PHP dataset or seeder.
 */

export const validTechnology = {
  key: 'runtime-test',
  name: 'Runtime Test',
  category: 'backend',
  icon: null,
} as const;

export const validProfile = {
  name: 'Test Person',
  headline: 'Test Headline',
  short_summary: 'Test summary.',
  introduction: 'Test introduction.',
  availability: 'Test availability.',
  cta: 'Test CTA',
  photo: null,
} as const;

export const validMediaWithAlt = {
  url: '/storage/runtime-test/photo.jpg',
  alt: 'Test alt text',
} as const;

export const validMediaIcon = {
  url: '/storage/runtime-test/icon.svg',
} as const;

export const validTechnologyWithIcon = {
  ...validTechnology,
  key: 'runtime-test-icon',
  icon: validMediaIcon,
} as const;

export const validExperience = {
  key: 'runtime-test-experience',
  organization: null,
  role: 'Test Role',
  start: '2020-01',
  end: null,
  summary: 'Test experience summary.',
  highlights: ['Test highlight one.', 'Test highlight two.'],
  technologies: [validTechnology],
} as const;

export const validWorkCase = {
  key: 'runtime-test-work-case',
  title: 'Test Work Case',
  context: 'Test context.',
  problem: 'Test problem.',
  contribution: 'Test contribution.',
  technical_approach: 'Test technical approach.',
  outcome: 'Test outcome.',
  technologies: [],
} as const;

export const validProject = {
  key: 'runtime-test-project',
  title: 'Test Project',
  summary: 'Test project summary.',
  problem: 'Test project problem.',
  solution: 'Test project solution.',
  featured: false,
  image: null,
  demo_url: null,
  repository_url: null,
  technologies: [],
} as const;

/** The four canonical technology groups, in the exact Phase 4 order. */
export const validTechnologyGroups = [
  { key: 'backend', label: 'Test Backend' },
  { key: 'data', label: 'Test Data' },
  { key: 'integration', label: 'Test Integration' },
  { key: 'collaboration', label: 'Test Collaboration' },
] as const;

export const validSite = {
  projects_empty_message: 'Test projects empty message.',
  contact_intro: 'Test contact intro.',
  technology_groups: [
    { key: 'backend', label: 'Test Backend' },
    { key: 'data', label: 'Test Data' },
    { key: 'integration', label: 'Test Integration' },
    { key: 'collaboration', label: 'Test Collaboration' },
  ],
  professional_links: [
    {
      key: 'linkedin',
      label: 'Test LinkedIn',
      href: 'https://example.test/in/test-person',
    },
    { key: 'github', label: 'Test GitHub', href: 'https://example.test/test-person' },
    { key: 'email', label: 'Test Email', href: 'mailto:test-person@example.test' },
  ],
  expertise_areas: [
    { key: 'runtime-test-area', title: 'Test Area', description: null },
    {
      key: 'runtime-test-area-described',
      title: 'Test Area Described',
      description: 'Test area description.',
    },
  ],
  work_principles: [
    { key: 'runtime-test-principle', statement: 'Test principle statement.' },
  ],
  cv: { url: '/cv/runtime-test.pdf', label: 'Test CV' },
} as const;

/** Technology groups in the wrong order (data before backend). */
export const technologyGroupsWrongOrder = [
  { key: 'data', label: 'Test Data' },
  { key: 'backend', label: 'Test Backend' },
  { key: 'integration', label: 'Test Integration' },
  { key: 'collaboration', label: 'Test Collaboration' },
] as const;

/** Technology groups with a duplicate key and a missing canonical key. */
export const technologyGroupsDuplicate = [
  { key: 'backend', label: 'Test Backend' },
  { key: 'backend', label: 'Test Backend Again' },
  { key: 'integration', label: 'Test Integration' },
  { key: 'collaboration', label: 'Test Collaboration' },
] as const;

/** Technology groups with a fifth, non-canonical entry appended. */
export const technologyGroupsExtraEntry = [
  { key: 'backend', label: 'Test Backend' },
  { key: 'data', label: 'Test Data' },
  { key: 'integration', label: 'Test Integration' },
  { key: 'collaboration', label: 'Test Collaboration' },
  { key: 'frontend', label: 'Test Frontend' },
] as const;

/** A Technology whose category is outside the closed enum. */
export const malformedTechnology = {
  ...validTechnology,
  category: 'frontend',
} as const;
