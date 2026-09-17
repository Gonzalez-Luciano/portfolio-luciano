/**
 * Browser client for the Laravel public API v1 (docs/api/PUBLIC_API_V1.md).
 * Every body is parsed as `unknown` and validated before it reaches the UI.
 * Requests are root-relative: the portfolio gateway serves the front and
 * `/api/*` from the same origin, so no CORS or API origin configuration exists.
 */

export type Locale = 'es' | 'en'

export type Statement = { lead: string; emphasis: string; tail: string }
export type Closing = { line_one: string; line_two: string }
export type Photo = { url: string; alt: string }
export type WorkMode = 'on_site' | 'hybrid' | 'remote'

export type Profile = {
  name: string
  location: string | null
  work_modes: WorkMode[]
  headline: string
  short_summary: string
  introduction: string
  availability: string
  statement: Statement | null
  closing: Closing | null
  cta: string
  photo: Photo | null
}

export type ProfessionalLinkKey = 'linkedin' | 'github' | 'email'
export type ProfessionalLink = { key: ProfessionalLinkKey; label: string; href: string }
export type CvLink = { url: string; label: string }
export type TechnologyCategory = 'backend' | 'data' | 'integration' | 'collaboration'
export type TechnologyGroup = { key: TechnologyCategory; label: string }
export type WorkPrinciple = { key: string; statement: string }
export type EducationItem = {
  key: string
  institution: string
  program: string
  detail: string | null
  start_year: number | null
  end_year: number | null
}
export type LanguageLevel = 'native' | 'a1' | 'a2' | 'b1' | 'b2' | 'c1' | 'c2'
export type LanguageItem = { key: string; name: string; level: LanguageLevel }

export type Site = {
  projects_empty_message: string
  contact_intro: string
  technology_groups: TechnologyGroup[]
  professional_links: ProfessionalLink[]
  work_principles: WorkPrinciple[]
  education: EducationItem[]
  languages: LanguageItem[]
  cv: CvLink | null
}

export type Technology = { key: string; name: string; category: TechnologyCategory }

export type Experience = {
  key: string
  organization: string | null
  role: string
  /** `YYYY-MM` */
  start: string
  /** `YYYY-MM`, or `null` for a current role */
  end: string | null
  summary: string
  highlights: string[]
  technologies: Technology[]
}

export type WorkCase = {
  key: string
  experience_key: string | null
  title: string
  context: string
  problem: string
  contribution: string
  technical_approach: string
  outcome: string
  technologies: Technology[]
}

export type ProjectKind = 'client' | 'personal'
export type DeliveryStatus = 'in_production' | 'in_use' | 'public_demo' | 'in_development'
export type ProjectImage = { url: string; alt: string }

export type Project = {
  key: string
  kind: ProjectKind
  client_name: string | null
  title: string
  role: string
  status: DeliveryStatus
  summary: string
  problem: string
  solution: string
  result: string
  featured: boolean
  images: ProjectImage[]
  demo_url: string | null
  repository_url: string | null
  technologies: Technology[]
}

export type StructuralContent = { profile: Profile; site: Site }

export type RegionData = {
  experiences: Experience[]
  'work-cases': WorkCase[]
  projects: Project[]
  technologies: Technology[]
}

export type Region = keyof RegionData

export const REGIONS: readonly Region[] = ['experiences', 'work-cases', 'projects', 'technologies']

export class ApiError extends Error {
  override name = 'ApiError'
}

type Validator<T> = (value: unknown) => value is T

const LINK_KEYS: readonly string[] = ['linkedin', 'github', 'email']
const TECHNOLOGY_CATEGORIES: readonly string[] = ['backend', 'data', 'integration', 'collaboration']
const WORK_MODES: readonly string[] = ['on_site', 'hybrid', 'remote']
const LANGUAGE_LEVELS: readonly string[] = ['native', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2']
const PROJECT_KINDS: readonly string[] = ['client', 'personal']
const DELIVERY_STATUSES: readonly string[] = ['in_production', 'in_use', 'public_demo', 'in_development']
const MONTH = /^\d{4}-(0[1-9]|1[0-2])$/

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function hasStrings(value: unknown, keys: readonly string[]): value is Record<string, string> {
  return isRecord(value) && keys.every((key) => typeof value[key] === 'string')
}

function isNullableString(value: unknown): value is string | null {
  return value === null || typeof value === 'string'
}

function isNullableYear(value: unknown): value is number | null {
  return value === null || (typeof value === 'number' && Number.isInteger(value))
}

function listOf<T>(validate: Validator<T>): Validator<T[]> {
  return (value: unknown): value is T[] => Array.isArray(value) && value.every(validate)
}

function isOneOf(value: unknown, allowed: readonly string[]): boolean {
  return typeof value === 'string' && allowed.includes(value)
}

const isPhoto: Validator<Photo> = (value): value is Photo => hasStrings(value, ['url', 'alt'])

export function isProfile(value: unknown): value is Profile {
  if (!hasStrings(value, ['name', 'headline', 'short_summary', 'introduction', 'availability', 'cta'])) return false
  const record = value as Record<string, unknown>
  return (
    isNullableString(record.location) &&
    Array.isArray(record.work_modes) &&
    record.work_modes.every((mode) => isOneOf(mode, WORK_MODES)) &&
    (record.statement === null || hasStrings(record.statement, ['lead', 'emphasis', 'tail'])) &&
    (record.closing === null || hasStrings(record.closing, ['line_one', 'line_two'])) &&
    (record.photo === null || isPhoto(record.photo))
  )
}

const isProfessionalLink: Validator<ProfessionalLink> = (value): value is ProfessionalLink =>
  hasStrings(value, ['key', 'label', 'href']) && isOneOf(value.key, LINK_KEYS)

const isTechnologyGroup: Validator<TechnologyGroup> = (value): value is TechnologyGroup =>
  hasStrings(value, ['key', 'label']) && isOneOf(value.key, TECHNOLOGY_CATEGORIES)

const isWorkPrinciple: Validator<WorkPrinciple> = (value): value is WorkPrinciple =>
  hasStrings(value, ['key', 'statement'])

const isEducationItem: Validator<EducationItem> = (value): value is EducationItem =>
  hasStrings(value, ['key', 'institution', 'program']) &&
  isNullableString((value as Record<string, unknown>).detail) &&
  isNullableYear((value as Record<string, unknown>).start_year) &&
  isNullableYear((value as Record<string, unknown>).end_year)

const isLanguageItem: Validator<LanguageItem> = (value): value is LanguageItem =>
  hasStrings(value, ['key', 'name', 'level']) && isOneOf(value.level, LANGUAGE_LEVELS)

export function isSite(value: unknown): value is Site {
  if (!hasStrings(value, ['projects_empty_message', 'contact_intro'])) return false
  const record = value as Record<string, unknown>
  return (
    listOf(isTechnologyGroup)(record.technology_groups) &&
    listOf(isProfessionalLink)(record.professional_links) &&
    listOf(isWorkPrinciple)(record.work_principles) &&
    listOf(isEducationItem)(record.education) &&
    listOf(isLanguageItem)(record.languages) &&
    (record.cv === null || hasStrings(record.cv, ['url', 'label']))
  )
}

const isTechnology: Validator<Technology> = (value): value is Technology =>
  hasStrings(value, ['key', 'name', 'category']) && isOneOf(value.category, TECHNOLOGY_CATEGORIES)

export const isTechnologyList: Validator<Technology[]> = listOf(isTechnology)

const isExperience: Validator<Experience> = (value): value is Experience => {
  if (!hasStrings(value, ['key', 'role', 'start', 'summary'])) return false
  const record = value as Record<string, unknown>
  return (
    MONTH.test(value.start) &&
    isNullableString(record.organization) &&
    (record.end === null || (typeof record.end === 'string' && MONTH.test(record.end))) &&
    Array.isArray(record.highlights) &&
    record.highlights.every((highlight) => typeof highlight === 'string') &&
    isTechnologyList(record.technologies)
  )
}

export const isExperienceList: Validator<Experience[]> = listOf(isExperience)

const isWorkCase: Validator<WorkCase> = (value): value is WorkCase =>
  hasStrings(value, ['key', 'title', 'context', 'problem', 'contribution', 'technical_approach', 'outcome']) &&
  isNullableString((value as Record<string, unknown>).experience_key) &&
  isTechnologyList((value as Record<string, unknown>).technologies)

export const isWorkCaseList: Validator<WorkCase[]> = listOf(isWorkCase)

const isProject: Validator<Project> = (value): value is Project => {
  if (!hasStrings(value, ['key', 'kind', 'title', 'role', 'status', 'summary', 'problem', 'solution', 'result'])) return false
  const record = value as Record<string, unknown>
  return (
    isOneOf(record.kind, PROJECT_KINDS) &&
    isOneOf(record.status, DELIVERY_STATUSES) &&
    isNullableString(record.client_name) &&
    typeof record.featured === 'boolean' &&
    listOf(isPhoto)(record.images) &&
    isNullableString(record.demo_url) &&
    isNullableString(record.repository_url) &&
    isTechnologyList(record.technologies)
  )
}

export const isProjectList: Validator<Project[]> = listOf(isProject)

async function request<T>(path: string, validate: Validator<T>, signal: AbortSignal): Promise<T> {
  const response = await fetch(path, { headers: { accept: 'application/json' }, signal })
  if (!response.ok) throw new ApiError(`HTTP ${response.status} for ${path}`)

  const body: unknown = await response.json().catch(() => null)
  const data = isRecord(body) ? body.data : undefined
  if (!validate(data)) throw new ApiError(`Malformed response for ${path}`)
  return data
}

/** Profile and Site are structural: without them the page shows only a general error with Retry. */
export async function loadStructuralContent(locale: Locale, signal: AbortSignal): Promise<StructuralContent> {
  const [profile, site] = await Promise.all([
    request(`/api/v1/${locale}/profile`, isProfile, signal),
    request(`/api/v1/${locale}/site`, isSite, signal),
  ])
  return { profile, site }
}

const REGION_VALIDATORS: { [R in Region]: Validator<RegionData[R]> } = {
  experiences: isExperienceList,
  'work-cases': isWorkCaseList,
  projects: isProjectList,
  technologies: isTechnologyList,
}

/** A regional collection: its failure affects only its own section, which offers its own Retry. */
export function loadRegion<R extends Region>(locale: Locale, region: R, signal: AbortSignal): Promise<RegionData[R]> {
  return request(`/api/v1/${locale}/${region}`, REGION_VALIDATORS[region], signal)
}
