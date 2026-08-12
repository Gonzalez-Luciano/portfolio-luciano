export function initMenu() {
  const dialog = document.querySelector('#mobile-menu');
  const open = document.querySelector('[data-menu-open]');
  const close = dialog?.querySelector('[data-menu-close]');
  if (!dialog || !open || !close) return;

  const closeMenu = () => {
    dialog.close();
    document.documentElement.classList.remove('menu-open');
    open.focus();
  };

  open.addEventListener('click', () => {
    dialog.showModal();
    document.documentElement.classList.add('menu-open');
    close.focus();
  });
  close.addEventListener('click', closeMenu);
  dialog.addEventListener('cancel', (event) => {
    event.preventDefault();
    closeMenu();
  });
  dialog.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', () => {
      dialog.close();
      document.documentElement.classList.remove('menu-open');
    });
  });
}
