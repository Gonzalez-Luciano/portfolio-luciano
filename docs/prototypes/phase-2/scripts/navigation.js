const destinationSelector = 'h1, h2, h3, [role="heading"]';

function focusDestination(hash) {
  const destination = document.querySelector(hash);
  const heading = destination?.querySelector(destinationSelector) ?? destination;
  if (!heading) return;

  const hadTabIndex = heading.hasAttribute('tabindex');
  if (!hadTabIndex) {
    heading.setAttribute('tabindex', '-1');
    heading.addEventListener('blur', () => heading.removeAttribute('tabindex'), { once: true });
  }
  heading.focus({ preventScroll: true });
}

export function initNavigation() {
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', () => {
      const hash = link.getAttribute('href');
      if (hash && hash !== '#') window.setTimeout(() => focusDestination(hash), 100);
    });
  });

  if (!('IntersectionObserver' in window)) return;

  const navigationLinks = [...document.querySelectorAll('[data-desktop-nav] a[href^="#"], [data-mobile-nav] a[href^="#"]')]
    .filter((link) => link.getAttribute('href') !== '#top');
  const sections = [...document.querySelectorAll('section[id]')]
    .filter((section) => section.id !== 'top');
  if (!navigationLinks.length || !sections.length) return;

  const setCurrent = (id) => {
    navigationLinks.forEach((link) => {
      if (link.getAttribute('href') === `#${id}`) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    });
  };

  const observer = new IntersectionObserver((entries) => {
    const intersecting = entries.filter((entry) => entry.isIntersecting);
    if (intersecting.length) setCurrent(intersecting.at(-1).target.id);
  }, { rootMargin: '-25% 0px -65% 0px' });

  sections.forEach((section) => observer.observe(section));
}
