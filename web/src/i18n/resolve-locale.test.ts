import {beforeEach, describe, expect, it} from 'vitest';
import {resolveLocale} from './resolve-locale';

describe('resolveLocale', () => {
  beforeEach(() => {
    document.cookie = 'portfolio_locale=; Max-Age=0; Path=/';
  });

  it('keeps a valid explicit cookie ahead of the browser preference', () => {
    expect(resolveLocale('en', 'es-AR,es;q=0.9')).toBe('en');
  });

  it('ignores an invalid cookie and selects the highest-quality supported language', () => {
    expect(resolveLocale('invalid', 'es-AR;q=0.4,en-US;q=0.9')).toBe('en');
  });

  it('falls back to Spanish when no supported language is available', () => {
    expect(resolveLocale(undefined, 'fr-FR,fr;q=0.9')).toBe('es');
    expect(resolveLocale(undefined, null)).toBe('es');
  });

  it('only resolves a locale and never persists a cookie', () => {
    expect(resolveLocale(undefined, 'en-US,en;q=0.9')).toBe('en');
    expect(document.cookie).toBe('');
  });
});
