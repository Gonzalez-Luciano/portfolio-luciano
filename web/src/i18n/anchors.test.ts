import {describe, expect, it} from 'vitest';
import {
  PRIMARY_DESTINATIONS,
  PRESERVED_FRAGMENT_IDS,
  isPreservedFragment,
  localeHref,
} from './anchors';

describe('PRIMARY_DESTINATIONS', () => {
  it('lists the five primary navigation destinations in the fixed order', () => {
    expect(PRIMARY_DESTINATIONS.map((destination) => destination.id)).toEqual([
      'work',
      'expertise',
      'projects',
      'approach',
      'contact',
    ]);
  });

  it('exposes a stable label lookup key for every destination', () => {
    for (const destination of PRIMARY_DESTINATIONS) {
      expect(typeof destination.id).toBe('string');
      expect(typeof destination.labelKey).toBe('string');
      expect(destination.labelKey.length).toBeGreaterThan(0);
    }
  });
});

describe('PRESERVED_FRAGMENT_IDS', () => {
  it('is exactly the eleven cross-locale-preserved and focus-managed ids', () => {
    expect([...PRESERVED_FRAGMENT_IDS].sort()).toEqual(
      [
        'work',
        'expertise',
        'projects',
        'approach',
        'contact',
        'about',
        'work-cases',
        'experience',
        'specialties',
        'technologies',
        'work-principles',
      ].sort(),
    );
    expect(PRESERVED_FRAGMENT_IDS).toHaveLength(11);
  });

  it('deliberately excludes #top', () => {
    expect(PRESERVED_FRAGMENT_IDS).not.toContain('top');
  });
});

describe('isPreservedFragment', () => {
  it('accepts an allowlisted id with a leading hash', () => {
    expect(isPreservedFragment('#projects')).toBe(true);
    expect(isPreservedFragment('#work-principles')).toBe(true);
  });

  it('accepts a bare allowlisted id', () => {
    expect(isPreservedFragment('experience')).toBe(true);
  });

  it('rejects #top', () => {
    expect(isPreservedFragment('#top')).toBe(false);
    expect(isPreservedFragment('top')).toBe(false);
  });

  it('rejects empty, unknown, and missing values', () => {
    expect(isPreservedFragment('')).toBe(false);
    expect(isPreservedFragment('#unknown')).toBe(false);
    expect(isPreservedFragment('#')).toBe(false);
    expect(isPreservedFragment(undefined as unknown as string)).toBe(false);
  });
});

describe('localeHref', () => {
  it('returns the locale root when no hash is supplied', () => {
    expect(localeHref('en')).toBe('/en');
    expect(localeHref('es')).toBe('/es');
  });

  it('preserves an allowlisted fragment for the target locale', () => {
    expect(localeHref('en', '#projects')).toBe('/en#projects');
    expect(localeHref('es', '#experience')).toBe('/es#experience');
    expect(localeHref('en', 'projects')).toBe('/en#projects');
  });

  it('drops #top and unknown or empty fragments', () => {
    expect(localeHref('en', '#top')).toBe('/en');
    expect(localeHref('en', '#unknown')).toBe('/en');
    expect(localeHref('en', '')).toBe('/en');
  });
});
