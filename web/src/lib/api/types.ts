export type ApiSuccess<T> = {
  data: T;
};

export type ApiError = {
  error: {
    code: string;
    message: string;
    details: Record<string, string[]>;
  };
};

export type ApiResult<T> = ApiSuccess<T> | ApiError;

/**
 * Public portfolio contracts consumed by the Phase 5 Next.js site.
 *
 * These describe a *valid, published* Phase 4 resource as documented in
 * `docs/api/PUBLIC_API_V1.md` and the approved design spec sections 10-13.
 * Draft-time nullability of a database column does not make a field nullable
 * here: only the fields marked `| null` below are nullable in the public
 * contract. Every HTTP body still begins as `unknown` and is narrowed to one
 * of these types exclusively through the runtime guards in `./validators`.
 */

export type TechnologyCategory =
  'backend' | 'data' | 'integration' | 'collaboration';

/** A decorative media reference. `alt` is intentionally absent. */
export type MediaIcon = {
  url: string;
};

/** A meaningful media reference that always carries alt text. */
export type MediaWithAlt = {
  url: string;
  alt: string;
};

export type Technology = {
  key: string;
  name: string;
  category: TechnologyCategory;
  icon: MediaIcon | null;
};

export type Profile = {
  name: string;
  headline: string;
  short_summary: string;
  introduction: string;
  availability: string;
  cta: string;
  photo: MediaWithAlt | null;
};

export type Experience = {
  key: string;
  organization: string | null;
  role: string;
  /** `YYYY-MM`, zero-padded month. */
  start: string;
  /** `YYYY-MM`, zero-padded month; `null` means the experience is current. */
  end: string | null;
  summary: string;
  highlights: string[];
  technologies: Technology[];
};

export type WorkCase = {
  key: string;
  title: string;
  context: string;
  problem: string;
  contribution: string;
  technical_approach: string;
  outcome: string;
  technologies: Technology[];
};

export type Project = {
  key: string;
  title: string;
  summary: string;
  problem: string;
  solution: string;
  featured: boolean;
  image: MediaWithAlt | null;
  demo_url: string | null;
  repository_url: string | null;
  technologies: Technology[];
};

export type TechnologyGroup = {
  key: TechnologyCategory;
  label: string;
};

export type ProfessionalLinkKey = 'linkedin' | 'github' | 'email';

export type ProfessionalLink = {
  key: ProfessionalLinkKey;
  label: string;
  href: string;
};

export type ExpertiseArea = {
  key: string;
  title: string;
  description: string | null;
};

export type WorkPrinciple = {
  key: string;
  statement: string;
};

export type CvReference = {
  url: string;
  label: string;
};

export type SiteConfiguration = {
  projects_empty_message: string;
  contact_intro: string;
  technology_groups: TechnologyGroup[];
  professional_links: ProfessionalLink[];
  expertise_areas: ExpertiseArea[];
  work_principles: WorkPrinciple[];
  cv: CvReference | null;
};

/**
 * The six logical public endpoints, named after their Laravel route segments.
 */
export type EndpointName =
  | 'profile'
  | 'site'
  | 'experiences'
  | 'work-cases'
  | 'projects'
  | 'technologies';

/** Normalised, safe categories for a known endpoint failure (spec section 12). */
export type EndpointFailureKind =
  'configuration' | 'network' | 'http' | 'malformed';

/**
 * A safe, diagnostic-only description of a failed endpoint acquisition. It
 * never carries a response body or an internal origin.
 */
export type EndpointFailure = {
  endpoint: EndpointName;
  kind: EndpointFailureKind;
  status?: number;
};

export type EndpointResult<T> =
  {ok: true; data: T} | {ok: false; failure: EndpointFailure};

/**
 * The coordinated loader result (spec section 13). Field names are the
 * camelCase forms of the endpoints; note `workCases`.
 */
export type PublicPortfolioResults = {
  profile: EndpointResult<Profile>;
  site: EndpointResult<SiteConfiguration>;
  experiences: EndpointResult<Experience[]>;
  workCases: EndpointResult<WorkCase[]>;
  projects: EndpointResult<Project[]>;
  technologies: EndpointResult<Technology[]>;
};
