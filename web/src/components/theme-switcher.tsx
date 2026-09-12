'use client';

import {useState, useSyncExternalStore} from 'react';
import {applyTheme, themeStorageKey, type Theme} from '@/theme/theme';

type ThemeSwitcherProps = {
  label: string;
  lightLabel: string;
  darkLabel: string;
};

function getAppliedTheme(): Theme {
  return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
}

function subscribeToAppliedTheme() {
  return () => {};
}

function getServerTheme(): Theme | null {
  return null;
}

export function ThemeSwitcher({
  label,
  lightLabel,
  darkLabel,
}: ThemeSwitcherProps) {
  const appliedTheme = useSyncExternalStore(
    subscribeToAppliedTheme,
    getAppliedTheme,
    getServerTheme,
  );
  const [selectedTheme, setSelectedTheme] = useState<Theme | null>(null);
  const theme = selectedTheme ?? appliedTheme ?? 'light';
  const isDark = theme === 'dark';

  function toggleTheme() {
    const nextTheme: Theme = isDark ? 'light' : 'dark';
    applyTheme(nextTheme, document.documentElement);
    setSelectedTheme(nextTheme);

    try {
      window.localStorage.setItem(themeStorageKey, nextTheme);
    } catch {
      // Storage may be unavailable in privacy-restricted browser contexts.
    }
  }

  return (
    <section className="theme-switcher" aria-label={label}>
      <div className="theme-switcher__controls js-only">
        <button type="button" aria-pressed={isDark} onClick={toggleTheme}>
          {isDark ? darkLabel : lightLabel}
        </button>
      </div>
    </section>
  );
}
