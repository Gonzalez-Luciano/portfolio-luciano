import {afterEach, beforeAll, describe, expect, it, vi} from 'vitest';
import {createRef, type ComponentProps} from 'react';
import {
  act,
  cleanup,
  fireEvent,
  render,
  screen,
  within,
} from '@testing-library/react';
import {MobileNavigation} from './mobile-navigation';

/**
 * jsdom ships only a stub `HTMLDialogElement`: `showModal`/`close` are not
 * functional and Escape does not trigger a real `cancel`. We polyfill just
 * enough to observe the `open` attribute and to fire the `close` event so the
 * coordinator's state syncing can be asserted.
 *
 * jsdom CANNOT prove native modality, real focus trapping, real Escape
 * handling, background inertness, or scroll behavior — Task 15 real-browser QA
 * owns those. Nothing here asserts them.
 */
const showModal = vi.fn(function showModalPolyfill(this: HTMLDialogElement) {
  this.setAttribute('open', '');
});
const close = vi.fn(function closePolyfill(this: HTMLDialogElement) {
  if (!this.hasAttribute('open')) {
    return;
  }

  this.removeAttribute('open');
  this.dispatchEvent(new Event('close'));
});

beforeAll(() => {
  HTMLDialogElement.prototype.showModal = showModal;
  HTMLDialogElement.prototype.close = close;
});

const labels = {
  open: 'Menú',
  close: 'Cerrar',
  title: 'Navegación',
  navLabel: 'Navegación principal',
  destinations: {
    work: 'Trabajo',
    expertise: 'Especialización',
    projects: 'Proyectos',
    approach: 'Forma de trabajo',
    contact: 'Contacto',
  },
  language: {
    label: 'Seleccionar idioma',
    spanish: 'Español',
    english: 'English',
  },
} as const;

type MediaListener = (event: MediaQueryListEvent) => void;

function installMatchMedia(initialMatches = false) {
  const listeners = new Set<MediaListener>();
  const mql = {
    matches: initialMatches,
    media: '(min-width: 64rem)',
    addEventListener: (_type: 'change', listener: MediaListener) =>
      listeners.add(listener),
    removeEventListener: (_type: 'change', listener: MediaListener) =>
      listeners.delete(listener),
  };

  vi.stubGlobal(
    'matchMedia',
    vi.fn(() => mql as unknown as MediaQueryList),
  );

  return {
    emit(matches: boolean) {
      mql.matches = matches;
      act(() => {
        listeners.forEach((listener) =>
          listener({matches} as MediaQueryListEvent),
        );
      });
    },
  };
}

function dialogEl(): HTMLDialogElement | null {
  return document.querySelector('dialog');
}

function renderMenu(
  overrides: Partial<ComponentProps<typeof MobileNavigation>> = {},
) {
  return render(
    <MobileNavigation locale="es" labels={labels} {...overrides} />,
  );
}

afterEach(() => {
  cleanup();
  showModal.mockClear();
  close.mockClear();
  document.body.style.overflow = '';
  vi.unstubAllGlobals();
});

describe('MobileNavigation', () => {
  it('exposes a real trigger button that opens a dialog named by the menu title', () => {
    renderMenu();

    const trigger = screen.getByRole('button', {name: 'Menú'});
    expect(trigger.tagName).toBe('BUTTON');

    fireEvent.click(trigger);

    expect(showModal).toHaveBeenCalledTimes(1);
    expect(dialogEl()?.hasAttribute('open')).toBe(true);
    expect(
      screen.getByRole('dialog', {name: 'Navegación'}),
    ).toBeInTheDocument();
  });

  it('keeps the trigger under the JS-only dead-control hiding contract', () => {
    renderMenu();

    expect(
      screen.getByRole('button', {name: 'Menú'}).closest('.js-only'),
    ).not.toBeNull();
  });

  it('moves initial focus inside the dialog on open', () => {
    renderMenu();

    fireEvent.click(screen.getByRole('button', {name: 'Menú'}));

    expect(dialogEl()?.contains(document.activeElement)).toBe(true);
  });

  it('closes on the explicit Close control and restores focus to the trigger', () => {
    renderMenu();
    const trigger = screen.getByRole('button', {name: 'Menú'});

    fireEvent.click(trigger);
    fireEvent.click(screen.getByRole('button', {name: 'Cerrar'}));

    expect(close).toHaveBeenCalled();
    expect(dialogEl()?.hasAttribute('open')).toBe(false);
    expect(document.activeElement).toBe(trigger);
  });

  it('closes and syncs state when the native cancel (Escape) event fires', () => {
    renderMenu();
    fireEvent.click(screen.getByRole('button', {name: 'Menú'}));

    act(() => {
      dialogEl()?.dispatchEvent(new Event('cancel'));
    });

    expect(dialogEl()?.hasAttribute('open')).toBe(false);
  });

  it('closes when a destination link is chosen', () => {
    renderMenu();
    fireEvent.click(screen.getByRole('button', {name: 'Menú'}));

    const dialog = dialogEl();
    expect(dialog).not.toBeNull();
    fireEvent.click(within(dialog!).getByRole('link', {name: 'Proyectos'}));

    expect(close).toHaveBeenCalled();
    expect(dialogEl()?.hasAttribute('open')).toBe(false);
  });

  it('closes when another locale is chosen', () => {
    renderMenu();
    fireEvent.click(screen.getByRole('button', {name: 'Menú'}));

    const dialog = dialogEl();
    const englishLink = within(dialog!).getByRole('link', {name: 'English'});
    const inertClick = new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
    });
    inertClick.preventDefault();
    fireEvent(englishLink, inertClick);

    expect(close).toHaveBeenCalled();
  });

  it('locks the document body scroll on open and restores the exact previous value on close', () => {
    renderMenu();
    document.body.style.overflow = 'auto';

    fireEvent.click(screen.getByRole('button', {name: 'Menú'}));
    expect(document.body.style.overflow).toBe('hidden');

    fireEvent.click(screen.getByRole('button', {name: 'Cerrar'}));
    expect(document.body.style.overflow).toBe('auto');
  });

  it('cleans up the open dialog and scroll lock on unmount', () => {
    const {unmount} = renderMenu();
    document.body.style.overflow = 'auto';

    fireEvent.click(screen.getByRole('button', {name: 'Menú'}));
    unmount();

    expect(close).toHaveBeenCalled();
    expect(document.body.style.overflow).toBe('auto');
  });

  it('on crossing into desktop while open: closes, releases scroll, and focuses the identity link — never the hidden trigger', () => {
    const media = installMatchMedia(false);

    const identity = document.createElement('a');
    identity.href = '#top';
    identity.textContent = 'Luciano González';
    document.body.append(identity);
    const identityRef = createRef<HTMLElement>();
    (identityRef as {current: HTMLElement | null}).current = identity;

    renderMenu({desktopFocusRef: identityRef});
    document.body.style.overflow = 'auto';
    const trigger = screen.getByRole('button', {name: 'Menú'});
    fireEvent.click(trigger);

    media.emit(true);

    expect(dialogEl()?.hasAttribute('open')).toBe(false);
    expect(document.body.style.overflow).toBe('auto');
    expect(document.activeElement).toBe(identity);
    expect(document.activeElement).not.toBe(trigger);

    identity.remove();
  });
});
