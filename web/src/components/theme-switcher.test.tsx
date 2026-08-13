import {cleanup, fireEvent, render, screen} from '@testing-library/react';
import {renderToStaticMarkup} from 'react-dom/server';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {ThemeSwitcher} from './theme-switcher';

const labels = {
  label: 'Theme',
  currentThemeLabel: 'Current theme',
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

  it('initializes from the theme already applied to the root element', () => {
    document.documentElement.dataset.theme = 'dark';
    window.localStorage.setItem('portfolio_theme', 'light');

    render(<ThemeSwitcher {...labels} />);

    expect(screen.getByRole('button', {name: 'Dark'})).toHaveAttribute(
      'aria-pressed',
      'true',
    );
    expect(screen.getByText('Current theme: Dark')).toBeInTheDocument();
  });

  it('renders statically without reading the browser document', () => {
    vi.stubGlobal('document', undefined);

    expect(() =>
      renderToStaticMarkup(<ThemeSwitcher {...labels} />),
    ).not.toThrow();
  });

  it('persists an explicit selection and keeps it after the control is recreated', () => {
    document.documentElement.dataset.theme = 'light';
    const {unmount} = render(<ThemeSwitcher {...labels} />);

    fireEvent.click(screen.getByRole('button', {name: 'Dark'}));

    expect(document.documentElement.dataset.theme).toBe('dark');
    expect(window.localStorage.getItem('portfolio_theme')).toBe('dark');

    unmount();
    render(<ThemeSwitcher {...labels} />);

    expect(screen.getByRole('button', {name: 'Dark'})).toHaveAttribute(
      'aria-pressed',
      'true',
    );
  });
});
