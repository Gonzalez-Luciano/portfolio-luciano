const storageKey = 'portfolio-prototype-theme';

export function initTheme() {
  const toggle = document.querySelector('[data-theme-toggle]');
  if (!toggle) return;

  const update = (theme) => {
    const isDark = theme === 'dark';
    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
    toggle.setAttribute('aria-pressed', String(isDark));
    toggle.setAttribute('aria-label', isDark ? toggle.dataset.labelDark : toggle.dataset.labelLight);
  };

  update(document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');
  toggle.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem(storageKey, theme);
    update(theme);
  });
}
