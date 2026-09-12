import {cleanup, fireEvent, render, screen} from '@testing-library/react';
import {renderToStaticMarkup} from 'react-dom/server';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {ThemeSwitcher} from './theme-switcher';

const labels = {
  label: 'Theme',
  lightLabel: 'Light',
  darkLabel: 'Dark',
};

describe('ThemeSwitcher', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    cleanup();
    document.documentElement.removeAttribute('data-theme');
    window.localStorage.clear();
  });

  it('renders a single toggle button reflecting the theme already applied to the root element', () => {
    document.documentElement.dataset.theme = 'dark';
    window.localStorage.setItem('portfolio_theme', 'light');

    render(<ThemeSwitcher {...labels} />);

    expect(screen.getAllByRole('button')).toHaveLength(1);
    expect(screen.getByRole('button', {name: 'Dark'})).toHaveAttribute(
      'aria-pressed',
      'true',
    );
  });

  it('never renders a "current theme" status text', () => {
    document.documentElement.dataset.theme = 'dark';

    render(<ThemeSwitcher {...labels} />);

    expect(screen.queryByText(/current theme/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/tema actual/i)).not.toBeInTheDocument();
  });

  it('exposes the region name from the label prop', () => {
    document.documentElement.dataset.theme = 'light';

    render(<ThemeSwitcher {...labels} />);

    expect(screen.getByRole('region', {name: 'Theme'})).toBeInTheDocument();
  });

  it('marks the toggle button with the shared JS-only styling hook', () => {
    document.documentElement.dataset.theme = 'light';

    render(<ThemeSwitcher {...labels} />);

    expect(
      screen.getByRole('button', {name: 'Light'}).closest('.js-only'),
    ).not.toBeNull();
  });

  it('renders statically without reading the browser document', () => {
    vi.stubGlobal('document', undefined);

    expect(() =>
      renderToStaticMarkup(<ThemeSwitcher {...labels} />),
    ).not.toThrow();
  });

  it('toggles the theme on click, persists it, and keeps it after the control is recreated', () => {
    document.documentElement.dataset.theme = 'light';
    const {unmount} = render(<ThemeSwitcher {...labels} />);

    fireEvent.click(screen.getByRole('button', {name: 'Light'}));

    expect(document.documentElement.dataset.theme).toBe('dark');
    expect(window.localStorage.getItem('portfolio_theme')).toBe('dark');

    unmount();
    render(<ThemeSwitcher {...labels} />);

    expect(screen.getByRole('button', {name: 'Dark'})).toHaveAttribute(
      'aria-pressed',
      'true',
    );
  });

  it('toggles back to light on a second click', () => {
    document.documentElement.dataset.theme = 'light';
    render(<ThemeSwitcher {...labels} />);

    fireEvent.click(screen.getByRole('button', {name: 'Light'}));
    expect(document.documentElement.dataset.theme).toBe('dark');

    fireEvent.click(screen.getByRole('button', {name: 'Dark'}));
    expect(document.documentElement.dataset.theme).toBe('light');
    expect(screen.getByRole('button', {name: 'Light'})).toHaveAttribute(
      'aria-pressed',
      'false',
    );
  });
});
