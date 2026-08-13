export const themeStorageKey = 'portfolio_theme';

export type Theme = 'light' | 'dark';

type ThemeStorage = Pick<Storage, 'getItem' | 'setItem' | 'removeItem'>;
type ThemeRoot = Pick<HTMLElement, 'dataset'>;

function isTheme(value: string | null): value is Theme {
  return value === 'light' || value === 'dark';
}

export function readStoredTheme(storage: ThemeStorage): Theme | null {
  const storedTheme = storage.getItem(themeStorageKey);

  if (isTheme(storedTheme)) {
    return storedTheme;
  }

  if (storedTheme !== null) {
    storage.removeItem(themeStorageKey);
  }

  return null;
}

export function getEffectiveTheme(
  storedTheme: Theme | null,
  prefersDark: boolean,
): Theme {
  return storedTheme ?? (prefersDark ? 'dark' : 'light');
}

export function applyTheme(theme: Theme, root: ThemeRoot): void {
  root.dataset.theme = theme;
}
