/**
 * Runtime validators for the six public portfolio contracts.
 *
 * Each HTTP body reaches these guards as `unknown` (after the success-envelope
 * check in `./client`) and is narrowed to exactly one public type from
 * `./types`. There are no `as Profile` style casts anywhere in the acquisition
 * path.
 *
 * Scope, per `docs/api/PUBLIC_API_V1.md` and design spec sections 10-11:
 *  - validate required object membership, every array element, primitives, the
 *    genuinely nullable fields, and nested media / link / cv / technology /
 *    date structures;
 *  - enforce the closed technology / professional-link enums;
 *  - enforce `YYYY-MM` structure and the 01-12 month range for experience
 *    dates;
 *  - enforce the four canonical technology groups in the exact documented
 *    order `backend, data, integration, collaboration`;
 *  - check URL *shape* structurally (root-relative `/storage/...`, `/cv/*.pdf`,
 *    `https://`, `mailto:`), not by strict RFC parsing;
 *  - tolerate additional unconsumed backend fields.
 *
 * These guards check the documented HTTP shape only. They do not decide
 * whether Laravel should publish a record, compute ordering, or reclassify
 * content.
 */

import type {
  CvReference,
  Experience,
  MediaIcon,
  MediaWithAlt,
  Profile,
  Project,
  SiteConfiguration,
  Technology,
  TechnologyCategory,
  WorkCase,
} from './types';

// --- Small composable guards ------------------------------------------------

export function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

export function isString(value: unknown): value is string {
  return typeof value === 'string';
}

export function isBoolean(value: unknown): value is boolean {
  return typeof value === 'boolean';
}

export function isNullable<T>(
  value: unknown,
  guard: (inner: unknown) => inner is T,
): value is T | null {
  return value === null || guard(value);
}

export function isArrayOf<T>(
  value: unknown,
  guard: (item: unknown) => item is T,
): value is T[] {
  return Array.isArray(value) && value.every((item) => guard(item));
}

// --- URL / date shape helpers --------------------------------------------------

/** A root-relative public storage reference, e.g. `/storage/media/photo.jpg`. */
function isStoragePath(value: unknown): value is string {
  return isString(value) && /^\/storage\/.+/.test(value);
}

/** The stable locale CV download route shape, e.g. `/cv/luciano-gonzalez-es.pdf`. */
function isCvPath(value: unknown): value is string {
  return isString(value) && /^\/cv\/.+\.pdf$/.test(value);
}

/** A structurally valid `https://` reference (no strict RFC parsing). */
function isHttpsUrl(value: unknown): value is string {
  return isString(value) && /^https:\/\/[^\s]+$/.test(value);
}

/** A structurally valid `mailto:` reference. */
function isMailto(value: unknown): value is string {
  return isString(value) && /^mailto:[^\s@]+@[^\s@]+$/.test(value);
}

/** `YYYY-MM` structure with a zero-padded month in the 01-12 range. */
function isYearMonth(value: unknown): value is string {
  if (!isString(value) || !/^\d{4}-\d{2}$/.test(value)) {
    return false;
  }

  const month = Number(value.slice(5, 7));

  return month >= 1 && month <= 12;
}

// --- Media -----------------------------------------------------------------

function isMediaIcon(value: unknown): value is MediaIcon {
  return isRecord(value) && isStoragePath(value.url);
}

function isMediaWithAlt(value: unknown): value is MediaWithAlt {
  return isRecord(value) && isStoragePath(value.url) && isString(value.alt);
}

// --- Technology -----------------------------------------------------------------

const TECHNOLOGY_CATEGORIES: readonly TechnologyCategory[] = [
  'backend',
  'data',
  'integration',
  'collaboration',
];

function isTechnologyCategory(value: unknown): value is TechnologyCategory {
  return (
    isString(value) &&
    (TECHNOLOGY_CATEGORIES as readonly string[]).includes(value)
  );
}

function isTechnology(value: unknown): value is Technology {
  return (
    isRecord(value) &&
    isString(value.key) &&
    isString(value.name) &&
    isTechnologyCategory(value.category) &&
    isNullable(value.icon, isMediaIcon)
  );
}

export function isTechnologyList(value: unknown): value is Technology[] {
  return isArrayOf(value, isTechnology);
}

// --- Profile -----------------------------------------------------------------

export function isProfile(value: unknown): value is Profile {
  return (
    isRecord(value) &&
    isString(value.name) &&
    isString(value.headline) &&
    isString(value.short_summary) &&
    isString(value.introduction) &&
    isString(value.availability) &&
    isString(value.cta) &&
    isNullable(value.photo, isMediaWithAlt)
  );
}

// --- Experience -----------------------------------------------------------------

function isExperience(value: unknown): value is Experience {
  return (
    isRecord(value) &&
    isString(value.key) &&
    isNullable(value.organization, isString) &&
    isString(value.role) &&
    isYearMonth(value.start) &&
    isNullable(value.end, isYearMonth) &&
    isString(value.summary) &&
    isArrayOf(value.highlights, isString) &&
    isTechnologyList(value.technologies)
  );
}

export function isExperienceList(value: unknown): value is Experience[] {
  return isArrayOf(value, isExperience);
}

// --- Work case -----------------------------------------------------------------

function isWorkCase(value: unknown): value is WorkCase {
  return (
    isRecord(value) &&
    isString(value.key) &&
    isString(value.title) &&
    isString(value.context) &&
    isString(value.problem) &&
    isString(value.contribution) &&
    isString(value.technical_approach) &&
    isString(value.outcome) &&
    isTechnologyList(value.technologies)
  );
}

export function isWorkCaseList(value: unknown): value is WorkCase[] {
  return isArrayOf(value, isWorkCase);
}

// --- Project -----------------------------------------------------------------

function isProject(value: unknown): value is Project {
  return (
    isRecord(value) &&
    isString(value.key) &&
    isString(value.title) &&
    isString(value.summary) &&
    isString(value.problem) &&
    isString(value.solution) &&
    isBoolean(value.featured) &&
    isNullable(value.image, isMediaWithAlt) &&
    isNullable(value.demo_url, isHttpsUrl) &&
    isNullable(value.repository_url, isHttpsUrl) &&
    isTechnologyList(value.technologies)
  );
}

export function isProjectList(value: unknown): value is Project[] {
  return isArrayOf(value, isProject);
}

// --- Site configuration ------------------------------------------------------

/**
 * Exactly the four canonical technology groups, in the exact documented order
 * `backend, data, integration, collaboration`, each with a string label.
 * Another membership or order is a documented-HTTP-contract violation and is
 * rejected here; nothing is sorted or reclassified.
 */
function isTechnologyGroupList(
  value: unknown,
): value is SiteConfiguration['technology_groups'] {
  if (!Array.isArray(value) || value.length !== TECHNOLOGY_CATEGORIES.length) {
    return false;
  }

  return TECHNOLOGY_CATEGORIES.every((category, index) => {
    const entry: unknown = value[index];

    return isRecord(entry) && entry.key === category && isString(entry.label);
  });
}

function isProfessionalLink(
  value: unknown,
): value is SiteConfiguration['professional_links'][number] {
  if (!isRecord(value) || !isString(value.label)) {
    return false;
  }

  // `href` shape is keyed by the closed link enum: LinkedIn/GitHub are HTTPS,
  // email is exposed as `mailto:` (spec section 10). An unknown key is rejected.
  switch (value.key) {
    case 'linkedin':
    case 'github':
      return isHttpsUrl(value.href);
    case 'email':
      return isMailto(value.href);
    default:
      return false;
  }
}

function isExpertiseArea(
  value: unknown,
): value is SiteConfiguration['expertise_areas'][number] {
  return (
    isRecord(value) &&
    isString(value.key) &&
    isString(value.title) &&
    isNullable(value.description, isString)
  );
}

function isWorkPrinciple(
  value: unknown,
): value is SiteConfiguration['work_principles'][number] {
  return isRecord(value) && isString(value.key) && isString(value.statement);
}

function isCvReference(value: unknown): value is CvReference {
  return isRecord(value) && isCvPath(value.url) && isString(value.label);
}

export function isSiteConfiguration(
  value: unknown,
): value is SiteConfiguration {
  return (
    isRecord(value) &&
    isString(value.projects_empty_message) &&
    isString(value.contact_intro) &&
    isTechnologyGroupList(value.technology_groups) &&
    isArrayOf(value.professional_links, isProfessionalLink) &&
    isArrayOf(value.expertise_areas, isExpertiseArea) &&
    isArrayOf(value.work_principles, isWorkPrinciple) &&
    isNullable(value.cv, isCvReference)
  );
}
