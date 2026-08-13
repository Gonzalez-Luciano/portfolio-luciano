import {fireEvent, render, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {LanguageSwitcher} from './language-switcher';

describe('LanguageSwitcher', () => {
  it('persists an explicitly selected language with the constrained cookie policy', () => {
    const cookieSetter = vi.spyOn(document, 'cookie', 'set');

    render(
      <LanguageSwitcher
        currentLocale="es"
        label="Select language"
        spanishLabel="Español"
        englishLabel="English"
      />,
    );

    const click = new MouseEvent('click', {bubbles: true, cancelable: true});
    click.preventDefault();
    fireEvent(screen.getByRole('link', {name: 'English'}), click);

    expect(cookieSetter).toHaveBeenCalledWith(
      'portfolio_locale=en; Path=/; Max-Age=31536000; SameSite=Lax',
    );
  });
});
