import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, fireEvent, render, screen} from '@testing-library/react';
import {LanguageSwitcher} from './language-switcher';

const labels = {
  label: 'Select language',
  spanishLabel: 'ES',
  englishLabel: 'EN',
} as const;

/** Fires a real, default-prevented click so jsdom never attempts navigation. */
function clickLink(name: string) {
  const click = new MouseEvent('click', {bubbles: true, cancelable: true});
  click.preventDefault();
  fireEvent(screen.getByRole('link', {name}), click);
}

describe('LanguageSwitcher', () => {
  afterEach(() => {
    cleanup();
    window.location.hash = '';
    vi.restoreAllMocks();
  });

  it('persists an explicitly selected language with the constrained cookie policy', () => {
    const cookieSetter = vi.spyOn(document, 'cookie', 'set');

    render(<LanguageSwitcher currentLocale="es" {...labels} />);
    clickLink('EN');

    expect(cookieSetter).toHaveBeenCalledWith(
      'portfolio_locale=en; Path=/; Max-Age=31536000; SameSite=Lax',
    );
  });

  it('marks the active locale with aria-current', () => {
    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    expect(screen.getByRole('link', {name: 'ES'})).toHaveAttribute(
      'aria-current',
      'page',
    );
    expect(screen.getByRole('link', {name: 'EN'})).not.toHaveAttribute(
      'aria-current',
    );
  });

  it('always links to the plain other-locale root, regardless of hash', () => {
    window.location.hash = '#work';
    render(<LanguageSwitcher currentLocale="en" {...labels} />);

    expect(screen.getByRole('link', {name: 'ES'})).toHaveAttribute(
      'href',
      '/es',
    );
  });

  it('invokes the optional onNavigate callback on activation', () => {
    const onNavigate = vi.fn();

    render(
      <LanguageSwitcher
        currentLocale="es"
        onNavigate={onNavigate}
        {...labels}
      />,
    );
    clickLink('EN');

    expect(onNavigate).toHaveBeenCalledTimes(1);
  });

  it('never invokes a scroll API on activation', () => {
    const scrollTo = vi.fn();
    const scrollIntoView = vi.fn();
    vi.stubGlobal('scrollTo', scrollTo);
    window.HTMLElement.prototype.scrollIntoView = scrollIntoView;

    render(<LanguageSwitcher currentLocale="es" {...labels} />);
    clickLink('EN');

    expect(scrollTo).not.toHaveBeenCalled();
    expect(scrollIntoView).not.toHaveBeenCalled();
    vi.unstubAllGlobals();
  });

  it('renders the compact ES/EN codes rather than the full language names', () => {
    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    expect(screen.queryByText('Español')).not.toBeInTheDocument();
    expect(screen.queryByText('English')).not.toBeInTheDocument();
    expect(screen.getByRole('link', {name: 'ES'})).toBeInTheDocument();
    expect(screen.getByRole('link', {name: 'EN'})).toBeInTheDocument();
  });

  it('gives the active-locale link the CSS hook the active-state styling relies on', () => {
    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    expect(screen.getByRole('link', {name: 'ES'})).toHaveClass(
      'language-switcher__link',
    );
  });
});
