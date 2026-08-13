import {render, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import englishMessages from '../../../messages/en.json';
import spanishMessages from '../../../messages/es.json';

const {notFound} = vi.hoisted(() => ({
  notFound: vi.fn(() => {
    throw new Error('not found');
  }),
}));

vi.mock('next/navigation', () => ({notFound}));
vi.mock('next-intl/server', () => ({
  setRequestLocale: vi.fn(),
  getTranslations:
    async ({locale}: {locale: string}) =>
    (key: string, values: Record<string, string> = {}) => {
      const messages = {
        title: `${locale} technical foundation`,
        currentLocale: `Current locale: ${values.locale}`,
        languageSwitcherLabel: 'Language',
        spanish: 'Spanish',
        english: 'English',
        themeLabel: 'Theme',
        currentThemeLabel: 'Current theme',
        lightTheme: 'Light',
        darkTheme: 'Dark',
        apiStatusSlot: 'API status placeholder',
      };

      return messages[key as keyof typeof messages];
    },
}));

import Page, {generateStaticParams} from './page';

function getKeys(value: Record<string, unknown>, prefix = ''): string[] {
  return Object.entries(value).flatMap(([key, child]) => {
    const path = prefix ? `${prefix}.${key}` : key;

    return typeof child === 'object' && child !== null
      ? getKeys(child as Record<string, unknown>, path)
      : [path];
  });
}

describe('localized foundation page', () => {
  it('prebuilds exactly the supported locale routes', () => {
    expect(generateStaticParams()).toEqual([{locale: 'es'}, {locale: 'en'}]);
  });

  it('rejects unknown locale route parameters', async () => {
    await expect(
      Page({params: Promise.resolve({locale: 'fr'})}),
    ).rejects.toThrow('not found');
    expect(notFound).toHaveBeenCalledOnce();
  });

  it('keeps the Spanish and English technical message keys aligned', () => {
    expect(getKeys(spanishMessages)).toEqual(getKeys(englishMessages));
  });

  it('renders a minimal server-rendered foundation without CMS content', async () => {
    render(await Page({params: Promise.resolve({locale: 'en'})}));

    expect(
      screen.getByRole('heading', {name: 'en technical foundation'}),
    ).toBeInTheDocument();
    expect(screen.getByText('Current locale: en')).toBeInTheDocument();
    expect(screen.getByRole('region', {name: 'Theme'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Check API'})).toBeInTheDocument();
  });
});
