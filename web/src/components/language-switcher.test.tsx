import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, fireEvent, render, screen} from '@testing-library/react';
import {LanguageSwitcher} from './language-switcher';

const labels = {
  label: 'Select language',
  spanishLabel: 'Español',
  englishLabel: 'English',
} as const;

function setHash(hash: string) {
  window.location.hash = hash;
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

    const click = new MouseEvent('click', {bubbles: true, cancelable: true});
    click.preventDefault();
    fireEvent(screen.getByRole('link', {name: 'English'}), click);

    expect(cookieSetter).toHaveBeenCalledWith(
      'portfolio_locale=en; Path=/; Max-Age=31536000; SameSite=Lax',
    );
  });

  it('marks the active locale with aria-current', () => {
    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    expect(screen.getByRole('link', {name: 'Español'})).toHaveAttribute(
      'aria-current',
      'page',
    );
    expect(screen.getByRole('link', {name: 'English'})).not.toHaveAttribute(
      'aria-current',
    );
  });

  it('links to the other locale root when there is no hash', () => {
    render(<LanguageSwitcher currentLocale="en" {...labels} />);

    expect(screen.getByRole('link', {name: 'Español'})).toHaveAttribute(
      'href',
      '/es',
    );
  });

  it('preserves an allowlisted fragment across the locale link (es -> en)', () => {
    setHash('#projects');

    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    expect(screen.getByRole('link', {name: 'English'})).toHaveAttribute(
      'href',
      '/en#projects',
    );
  });

  it('preserves an allowlisted fragment across the locale link (en -> es)', () => {
    setHash('#experience');

    render(<LanguageSwitcher currentLocale="en" {...labels} />);

    expect(screen.getByRole('link', {name: 'Español'})).toHaveAttribute(
      'href',
      '/es#experience',
    );
  });

  it('drops an unknown fragment from the locale link', () => {
    setHash('#foo');

    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    expect(screen.getByRole('link', {name: 'English'})).toHaveAttribute(
      'href',
      '/en',
    );
  });

  it('drops #top from the locale link', () => {
    setHash('#top');

    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    expect(screen.getByRole('link', {name: 'English'})).toHaveAttribute(
      'href',
      '/en',
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

    const click = new MouseEvent('click', {bubbles: true, cancelable: true});
    click.preventDefault();
    fireEvent(screen.getByRole('link', {name: 'English'}), click);

    expect(onNavigate).toHaveBeenCalledTimes(1);
  });

  it('never invokes a scroll API on activation', () => {
    const scrollTo = vi.fn();
    const scrollIntoView = vi.fn();
    vi.stubGlobal('scrollTo', scrollTo);
    window.HTMLElement.prototype.scrollIntoView = scrollIntoView;
    setHash('#projects');

    render(<LanguageSwitcher currentLocale="es" {...labels} />);

    const click = new MouseEvent('click', {bubbles: true, cancelable: true});
    click.preventDefault();
    fireEvent(screen.getByRole('link', {name: 'English'}), click);

    expect(scrollTo).not.toHaveBeenCalled();
    expect(scrollIntoView).not.toHaveBeenCalled();
    vi.unstubAllGlobals();
  });
});
