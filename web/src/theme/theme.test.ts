import {afterEach, describe, expect, it, vi} from 'vitest';
import {
  applyTheme,
  getEffectiveTheme,
  readStoredTheme,
  themeStorageKey,
} from './theme';
import {themeBootstrapSource} from './bootstrap';

describe('theme primitives', () => {
  afterEach(() => {
    document.documentElement.removeAttribute('data-theme');
    window.localStorage.clear();
    vi.unstubAllGlobals();
  });

  it('uses the system preference when no explicit theme has been stored', () => {
    expect(getEffectiveTheme(null, false)).toBe('light');
    expect(getEffectiveTheme(null, true)).toBe('dark');
  });

  it('uses a valid explicit stored theme ahead of the system preference', () => {
    expect(getEffectiveTheme('light', true)).toBe('light');
    expect(getEffectiveTheme('dark', false)).toBe('dark');
  });

  it('removes an invalid stored value before falling back to the system preference', () => {
    const storage = {
      getItem: vi.fn(() => 'sepia'),
      removeItem: vi.fn(),
      setItem: vi.fn(),
    };

    expect(readStoredTheme(storage)).toBeNull();
    expect(storage.removeItem).toHaveBeenCalledWith(themeStorageKey);
    expect(getEffectiveTheme(readStoredTheme(storage), true)).toBe('dark');
  });

  it('mutates the root data attribute with the selected theme', () => {
    applyTheme('dark', document.documentElement);

    expect(document.documentElement.dataset.theme).toBe('dark');
  });

  it('applies the effective theme before hydration from a stable bootstrap source', () => {
    vi.stubGlobal(
      'matchMedia',
      vi.fn(() => ({matches: true})),
    );
    window.localStorage.setItem(themeStorageKey, 'light');

    new Function(themeBootstrapSource)();

    expect(document.documentElement.dataset.theme).toBe('light');
  });
});
