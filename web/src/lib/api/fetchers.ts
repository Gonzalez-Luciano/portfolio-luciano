import type {Locale} from '@/i18n/routing';
import {requestPublicResource} from './client';
import type {PublicRequestTestOptions} from './client';
import type {
  EndpointResult,
  Experience,
  Profile,
  Project,
  SiteConfiguration,
  Technology,
  WorkCase,
} from './types';
import {
  isExperienceList,
  isProfile,
  isProjectList,
  isSiteConfiguration,
  isTechnologyList,
  isWorkCaseList,
} from './validators';

/**
 * The six focused endpoint fetchers for the Phase 5 public site.
 *
 * Each one binds a Phase 4 route segment to its runtime validator and delegates
 * to the shared server transport. They hold no presentation policy: an empty
 * published collection, a not-found singleton, and a malformed payload are all
 * returned verbatim as an {@link EndpointResult} for Task 3's loader to
 * coordinate and Task 10's sections to render.
 */

export function fetchProfile(
  locale: Locale,
  options?: PublicRequestTestOptions,
): Promise<EndpointResult<Profile>> {
  return requestPublicResource('profile', locale, isProfile, options);
}

export function fetchSite(
  locale: Locale,
  options?: PublicRequestTestOptions,
): Promise<EndpointResult<SiteConfiguration>> {
  return requestPublicResource('site', locale, isSiteConfiguration, options);
}

export function fetchExperiences(
  locale: Locale,
  options?: PublicRequestTestOptions,
): Promise<EndpointResult<Experience[]>> {
  return requestPublicResource(
    'experiences',
    locale,
    isExperienceList,
    options,
  );
}

export function fetchWorkCases(
  locale: Locale,
  options?: PublicRequestTestOptions,
): Promise<EndpointResult<WorkCase[]>> {
  return requestPublicResource('work-cases', locale, isWorkCaseList, options);
}

export function fetchProjects(
  locale: Locale,
  options?: PublicRequestTestOptions,
): Promise<EndpointResult<Project[]>> {
  return requestPublicResource('projects', locale, isProjectList, options);
}

export function fetchTechnologies(
  locale: Locale,
  options?: PublicRequestTestOptions,
): Promise<EndpointResult<Technology[]>> {
  return requestPublicResource(
    'technologies',
    locale,
    isTechnologyList,
    options,
  );
}
