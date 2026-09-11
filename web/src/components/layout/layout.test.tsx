import {afterEach, describe, expect, it} from 'vitest';
import {cleanup, render, screen} from '@testing-library/react';
import {renderToStaticMarkup} from 'react-dom/server';
import {PRIMARY_DESTINATIONS} from '@/i18n/anchors';
import {SiteHeader, type SiteHeaderLabels} from './site-header';

const labels: SiteHeaderLabels = {
  navLabel: 'Navegación principal',
  destinations: {
    work: 'Trabajo',
    expertise: 'Especialización',
    projects: 'Proyectos',
    approach: 'Forma de trabajo',
    contact: 'Contacto',
  },
  menu: {open: 'Menú', close: 'Cerrar', title: 'Navegación'},
  language: {
    label: 'Seleccionar idioma',
    spanish: 'Español',
    english: 'English',
  },
  theme: {
    label: 'Tema',
    current: 'Tema actual',
    light: 'Claro',
    dark: 'Oscuro',
  },
};

const expectedHrefs = PRIMARY_DESTINATIONS.map(
  (destination) => `#${destination.id}`,
);
const expectedLabels = PRIMARY_DESTINATIONS.map(
  (destination) =>
    labels.destinations[destination.id as keyof typeof labels.destinations],
);

function hashHrefsIn(root: Element | null): string[] {
  if (!root) {
    return [];
  }

  return [...root.querySelectorAll('a')]
    .map((anchor) => anchor.getAttribute('href'))
    .filter((href): href is string => href !== null && href.startsWith('#'));
}

/**
 * jsdom's React client renderer silently drops `<noscript>` children, so the
 * no-script fallback is asserted from server HTML. A DOMParser document has
 * scripting disabled, so it parses `<noscript>` content as real elements.
 */
function serverDoc(node: Parameters<typeof renderToStaticMarkup>[0]): Document {
  return new DOMParser().parseFromString(
    renderToStaticMarkup(node),
    'text/html',
  );
}

afterEach(cleanup);

describe('SiteHeader — valid variant', () => {
  it('renders the five shared destinations in order in the desktop, dialog, and no-script navigations', () => {
    const {container} = render(
      <SiteHeader locale="es" labels={labels} name="Luciano González" />,
    );
    const doc = serverDoc(
      <SiteHeader locale="es" labels={labels} name="Luciano González" />,
    );

    expect(
      hashHrefsIn(container.querySelector('#primary-navigation-desktop')),
    ).toEqual(expectedHrefs);
    expect(
      hashHrefsIn(container.querySelector('#primary-navigation-dialog')),
    ).toEqual(expectedHrefs);
    expect(hashHrefsIn(doc.querySelector('noscript nav'))).toEqual(
      expectedHrefs,
    );
  });

  it('sources every rendered destination label and order from PRIMARY_DESTINATIONS', () => {
    const {container} = render(<SiteHeader locale="es" labels={labels} />);

    const desktopNav = container.querySelector('#primary-navigation-desktop');
    const linkText = [...(desktopNav?.querySelectorAll('a') ?? [])].map(
      (anchor) => anchor.textContent?.replace(/\s+/g, ' ').trim(),
    );

    expectedLabels.forEach((label, index) => {
      expect(linkText[index]).toContain(label);
    });
    expect(linkText).toHaveLength(PRIMARY_DESTINATIONS.length);
  });

  it('points the identity link at #top', () => {
    render(<SiteHeader locale="es" labels={labels} name="Luciano González" />);

    expect(
      screen.getByRole('link', {name: 'Luciano González'}),
    ).toHaveAttribute('href', '#top');
  });

  it('gives the no-script fallback a named nav with the five anchors and a real other-locale link', () => {
    const doc = serverDoc(<SiteHeader locale="es" labels={labels} />);
    const nav = doc.querySelector('noscript nav');

    expect(nav?.getAttribute('aria-label')).toBe('Navegación principal');
    expect(hashHrefsIn(nav)).toEqual(expectedHrefs);

    const otherLocaleLink = [...(nav?.querySelectorAll('a') ?? [])].find(
      (anchor) => anchor.getAttribute('href') === '/en',
    );

    expect(otherLocaleLink).toBeTruthy();
    expect(otherLocaleLink?.textContent).toBe('English');
  });

  it('carries the CSS class that hides the no-script fallback at >= 64rem so it never duplicates the desktop bar (jsdom cannot evaluate the media query itself; a real browser applies it via this class)', () => {
    const doc = serverDoc(<SiteHeader locale="es" labels={labels} />);

    expect(doc.querySelector('noscript nav')?.className).toBe(
      'site-header__noscript-nav',
    );
  });

  it('keeps the Menu trigger and the theme controls under the JS-only hiding contract while native anchors stay outside it', () => {
    const {container} = render(<SiteHeader locale="es" labels={labels} />);
    const doc = serverDoc(<SiteHeader locale="es" labels={labels} />);

    expect(
      screen.getByRole('button', {name: 'Menú'}).closest('.js-only'),
    ).not.toBeNull();
    expect(
      screen.getByRole('button', {name: 'Claro'}).closest('.js-only'),
    ).not.toBeNull();

    expect(
      container
        .querySelector('#primary-navigation-desktop a')
        ?.closest('.js-only'),
    ).toBeNull();
    expect(doc.querySelector('noscript nav a')?.closest('.js-only')).toBeNull();
  });

  it('keeps the language and theme controls available', () => {
    const {container} = render(<SiteHeader locale="es" labels={labels} />);

    expect(
      container.querySelector(
        '.site-header__desktop nav[aria-label="Seleccionar idioma"]',
      ),
    ).toBeTruthy();
    expect(screen.getByRole('region', {name: 'Tema'})).toBeInTheDocument();
  });
});

describe('SiteHeader — structural failure variant', () => {
  it('omits the five professional destinations and the no-script nav but keeps language and theme controls', () => {
    const {container} = render(
      <SiteHeader locale="es" labels={labels} variant="structural-failure" />,
    );
    const doc = serverDoc(
      <SiteHeader locale="es" labels={labels} variant="structural-failure" />,
    );

    expect(container.querySelector('#primary-navigation-desktop')).toBeNull();
    expect(container.querySelector('#primary-navigation-dialog')).toBeNull();
    expect(doc.querySelector('noscript')).toBeNull();
    expect(screen.queryByRole('button', {name: 'Menú'})).toBeNull();
    expect(screen.queryByRole('link', {name: 'Trabajo'})).toBeNull();

    expect(
      screen.getByRole('navigation', {name: 'Seleccionar idioma'}),
    ).toBeInTheDocument();
    expect(screen.getByRole('region', {name: 'Tema'})).toBeInTheDocument();
  });

  it('omits the identity link when no CMS name is supplied', () => {
    const {container} = render(
      <SiteHeader locale="es" labels={labels} variant="structural-failure" />,
    );

    expect(container.querySelector('a[href="#top"]')).toBeNull();
  });
});
