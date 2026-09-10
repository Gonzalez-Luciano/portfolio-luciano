import {afterEach, describe, expect, it, vi} from 'vitest';
import {
  fetchExperiences,
  fetchProfile,
  fetchProjects,
  fetchSite,
  fetchTechnologies,
  fetchWorkCases,
} from './fetchers';
import type {EndpointName, EndpointResult} from './types';
import type {Locale} from '@/i18n/routing';

type Fetcher = (
  locale: Locale,
  options?: {fetchImpl?: typeof fetch},
) => Promise<EndpointResult<unknown>>;

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: {'content-type': 'application/json'},
  });
}

const validProfile = {
  name: 'Luciano',
  headline: 'Engineer',
  short_summary: 'Summary',
  introduction: 'Intro',
  availability: 'Open',
  cta: 'Contact',
  photo: null,
};

const validSite = {
  projects_empty_message: 'Nothing published yet.',
  contact_intro: 'Say hello.',
  technology_groups: [
    {key: 'backend', label: 'Backend'},
    {key: 'data', label: 'Data'},
    {key: 'integration', label: 'Integration'},
    {key: 'collaboration', label: 'Collaboration'},
  ],
  professional_links: [],
  expertise_areas: [],
  work_principles: [],
  cv: null,
};

type Case = {
  endpoint: EndpointName;
  segment: string;
  fetcher: Fetcher;
  valid: unknown;
  invalid: unknown;
  isCollection: boolean;
};

const cases: Case[] = [
  {
    endpoint: 'profile',
    segment: 'profile',
    fetcher: fetchProfile as Fetcher,
    valid: validProfile,
    invalid: {...validProfile, name: 42},
    isCollection: false,
  },
  {
    endpoint: 'site',
    segment: 'site',
    fetcher: fetchSite as Fetcher,
    valid: validSite,
    invalid: {...validSite, contact_intro: 5},
    isCollection: false,
  },
  {
    endpoint: 'experiences',
    segment: 'experiences',
    fetcher: fetchExperiences as Fetcher,
    valid: [],
    invalid: [{}],
    isCollection: true,
  },
  {
    endpoint: 'work-cases',
    segment: 'work-cases',
    fetcher: fetchWorkCases as Fetcher,
    valid: [],
    invalid: [{}],
    isCollection: true,
  },
  {
    endpoint: 'projects',
    segment: 'projects',
    fetcher: fetchProjects as Fetcher,
    valid: [],
    invalid: [{}],
    isCollection: true,
  },
  {
    endpoint: 'technologies',
    segment: 'technologies',
    fetcher: fetchTechnologies as Fetcher,
    valid: [],
    invalid: [{}],
    isCollection: true,
  },
];

describe('public endpoint fetchers', () => {
  afterEach(() => {
    vi.unstubAllEnvs();
  });

  it.each(cases)(
    '$endpoint issues one GET to /api/v1/{locale}/$segment and returns the validated body',
    async ({fetcher, segment, valid}) => {
      vi.stubEnv('INTERNAL_API_ORIGIN', 'http://api');
      const fetchImpl = vi.fn().mockResolvedValue(jsonResponse({data: valid}));

      const result = await fetcher('en', {fetchImpl});

      expect(result).toEqual({ok: true, data: valid});
      expect(fetchImpl).toHaveBeenCalledTimes(1);
      expect(fetchImpl).toHaveBeenCalledWith(
        `http://api/api/v1/en/${segment}`,
        expect.objectContaining({method: 'GET', cache: 'no-store'}),
      );
    },
  );

  it.each(cases.filter((entry) => entry.isCollection))(
    '$endpoint returns an empty collection as {ok:true,data:[]}',
    async ({fetcher}) => {
      vi.stubEnv('INTERNAL_API_ORIGIN', 'http://api');
      const fetchImpl = vi.fn().mockResolvedValue(jsonResponse({data: []}));

      await expect(fetcher('es', {fetchImpl})).resolves.toEqual({
        ok: true,
        data: [],
      });
    },
  );

  it.each(cases)(
    '$endpoint reports a malformed item as {ok:false,failure:{kind:"malformed",endpoint}}',
    async ({endpoint, fetcher, invalid}) => {
      vi.stubEnv('INTERNAL_API_ORIGIN', 'http://api');
      const fetchImpl = vi.fn().mockResolvedValue(jsonResponse({data: invalid}));

      await expect(fetcher('es', {fetchImpl})).resolves.toEqual({
        ok: false,
        failure: {endpoint, kind: 'malformed', status: 200},
      });
    },
  );
});
