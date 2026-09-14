/**
 * Browser client for the Laravel public API v1 (docs/api/PUBLIC_API_V1.md).
 * Every body is parsed as `unknown` and validated before it reaches the UI.
 * Requests are root-relative: the portfolio gateway serves the front and
 * `/api/*` from the same origin, so no CORS or API origin configuration exists.
 */

export type Locale = 'es' | 'en'

export type Statement = { lead: string; emphasis: string; tail: string }
export type Closing = { line_one: string; line_two: string }

export type Profile = {
  name: string
  headline: string
  short_summary: string
  availability: string
  statement: Statement | null
  closing: Closing | null
}

export type ProfessionalLinkKey = 'linkedin' | 'github' | 'email'
export type ProfessionalLink = { key: ProfessionalLinkKey; label: string; href: string }
export type CvLink = { url: string; label: string }
export type Site = { professional_links: ProfessionalLink[]; cv: CvLink | null }

export type TechnologyCategory = 'backend' | 'data' | 'integration' | 'collaboration'
export type Technology = { key: string; name: string; category: TechnologyCategory }

export type PublicContent = { profile: Profile; site: Site; technologies: Technology[] }

export class ApiError extends Error {
  override name = 'ApiError'
}

const LINK_KEYS: readonly string[] = ['linkedin', 'github', 'email']
const TECHNOLOGY_CATEGORIES: readonly string[] = ['backend', 'data', 'integration', 'collaboration']

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function hasStrings(value: unknown, keys: readonly string[]): value is Record<string, string> {
  return isRecord(value) && keys.every((key) => typeof value[key] === 'string')
}

export function isProfile(value: unknown): value is Profile {
  if (!isRecord(value) || !hasStrings(value, ['name', 'headline', 'short_summary', 'availability'])) return false
  const statementValid =
    value.statement === null || hasStrings(value.statement, ['lead', 'emphasis', 'tail'])
  const closingValid = value.closing === null || hasStrings(value.closing, ['line_one', 'line_two'])
  return statementValid && closingValid
}

function isProfessionalLink(value: unknown): value is ProfessionalLink {
  return hasStrings(value, ['key', 'label', 'href']) && LINK_KEYS.includes(value.key)
}

export function isSite(value: unknown): value is Site {
  if (!isRecord(value)) return false
  const links = value.professional_links
  const linksValid = Array.isArray(links) && links.every(isProfessionalLink)
  const cvValid = value.cv === null || hasStrings(value.cv, ['url', 'label'])
  return linksValid && cvValid
}

function isTechnology(value: unknown): value is Technology {
  return hasStrings(value, ['key', 'name', 'category']) && TECHNOLOGY_CATEGORIES.includes(value.category)
}

export function isTechnologyList(value: unknown): value is Technology[] {
  return Array.isArray(value) && value.every(isTechnology)
}

async function request<T>(
  path: string,
  validate: (value: unknown) => value is T,
  signal: AbortSignal,
): Promise<T> {
  const response = await fetch(path, { headers: { accept: 'application/json' }, signal })
  if (!response.ok) throw new ApiError(`HTTP ${response.status} for ${path}`)

  const body: unknown = await response.json().catch(() => null)
  const data = isRecord(body) ? body.data : undefined
  if (!validate(data)) throw new ApiError(`Malformed response for ${path}`)
  return data
}

/**
 * Profile and Site are structural: if either fails the scene cannot render
 * professional content. Technologies only feed a decorative eyebrow, so a
 * failure there degrades to an empty list instead of failing the page.
 */
export async function loadPublicContent(locale: Locale, signal: AbortSignal): Promise<PublicContent> {
  const base = `/api/v1/${locale}`

  const [profile, site, technologies] = await Promise.all([
    request(`${base}/profile`, isProfile, signal),
    request(`${base}/site`, isSite, signal),
    request(`${base}/technologies`, isTechnologyList, signal).catch((error: unknown) => {
      if (signal.aborted) throw error
      return [] as Technology[]
    }),
  ])

  return { profile, site, technologies }
}
