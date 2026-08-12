const storageKey = 'portfolio-prototype-theme';
const isTheme = (value) => value === 'light' || value === 'dark';

export function initTheme() {
  const toggle = document.querySelector('[data-theme-toggle]');
  if (!toggle) return;

  const update = (theme) => {
    const isDark = theme === 'dark';
    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
    toggle.setAttribute('aria-pressed', String(isDark));
    toggle.setAttribute('aria-label', isDark ? toggle.dataset.labelLight : toggle.dataset.labelDark);
  };

  const stored = localStorage.getItem(storageKey);
  const initialTheme = isTheme(stored)
    ? stored
    : (document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');
  update(initialTheme);
  toggle.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem(storageKey, theme);
    update(theme);
  });
}
