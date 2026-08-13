'use client';

import {useState, useSyncExternalStore} from 'react';
import {applyTheme, themeStorageKey, type Theme} from '@/theme/theme';

type ThemeSwitcherProps = {
  label: string;
  currentThemeLabel: string;
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
  currentThemeLabel,
  lightLabel,
  darkLabel,
}: ThemeSwitcherProps) {
  const appliedTheme = useSyncExternalStore(
    subscribeToAppliedTheme,
    getAppliedTheme,
    getServerTheme,
  );
  const [selectedTheme, setSelectedTheme] = useState<Theme | null>(null);
  const theme = selectedTheme ?? appliedTheme;

  function selectTheme(nextTheme: Theme) {
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
      <p aria-live="polite">
        {theme === null
          ? currentThemeLabel
          : `${currentThemeLabel}: ${theme === 'light' ? lightLabel : darkLabel}`}
      </p>
      <div className="theme-switcher__controls">
        <button
          type="button"
          aria-pressed={theme === 'light'}
          onClick={() => selectTheme('light')}
        >
          {lightLabel}
        </button>
        <button
          type="button"
          aria-pressed={theme === 'dark'}
          onClick={() => selectTheme('dark')}
        >
          {darkLabel}
        </button>
      </div>
    </section>
  );
}
