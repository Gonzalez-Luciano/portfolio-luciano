import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, fireEvent, render, screen} from '@testing-library/react';
import {renderToStaticMarkup} from 'react-dom/server';
import {NextIntlClientProvider} from 'next-intl';
import esMessages from '../../../messages/es.json';
import enMessages from '../../../messages/en.json';

const notFoundHarness = vi.hoisted(() => ({
  locale: 'es' as 'es' | 'en',
}));

vi.mock('next-intl/server', () => {
  const copy = {
    es: {
      'notFound.title': 'Página no encontrada',
      'notFound.explanation': 'La página solicitada no existe.',
      'notFound.returnAction': 'Volver al portfolio',
    },
    en: {
      'notFound.title': 'Page not found',
      'notFound.explanation': 'The requested page does not exist.',
      'notFound.returnAction': 'Back to portfolio',
    },
  } as const;

  return {
    getLocale: async () => notFoundHarness.locale,
    getTranslations: async (namespace?: string) => (key: string) => {
      const dictionary = copy[notFoundHarness.locale];
      const withNamespace = namespace
        ? (`${namespace}.${key}`.replace(
            /^Portfolio\./,
            '',
          ) as keyof typeof dictionary)
        : (key as keyof typeof dictionary);

      return dictionary[withNamespace] ?? key;
    },
  };
});

import Loading from './loading';
import LocalizedError from './error';
import NotFound from './not-found';
import GlobalError from '../global-error';

afterEach(cleanup);

describe('loading.tsx', () => {
  it('renders neutral skeleton geometry with no person, role, or project claim', () => {
    const {container} = render(<Loading />);

    expect(screen.queryAllByRole('heading')).toHaveLength(0);
    expect(container.textContent?.trim()).toBe('');
    expect(
      container.querySelectorAll('[data-skeleton]').length,
    ).toBeGreaterThan(2);
  });
});

describe('error.tsx', () => {
  it('shows the localized safe copy, hides technical detail, and recovers via reset()', () => {
    const reset = vi.fn();

    render(
      <NextIntlClientProvider locale="es" messages={esMessages}>
        <LocalizedError
          error={Object.assign(new Error('DB pool exhausted at 10.0.0.7'), {
            digest: 'deadbeef',
          })}
          reset={reset}
        />
      </NextIntlClientProvider>,
    );

    expect(
      screen.getByText('No pudimos cargar el contenido del portfolio.'),
    ).toBeInTheDocument();
    expect(screen.queryByText(/DB pool exhausted/)).toBeNull();
    expect(screen.queryByText(/10\.0\.0\.7/)).toBeNull();
    expect(screen.queryByText(/deadbeef/)).toBeNull();

    fireEvent.click(screen.getByRole('button', {name: 'Reintentar'}));
    expect(reset).toHaveBeenCalledTimes(1);
  });

  it('renders the English safe copy under the English provider', () => {
    render(
      <NextIntlClientProvider locale="en" messages={enMessages}>
        <LocalizedError error={new Error('boom')} reset={vi.fn()} />
      </NextIntlClientProvider>,
    );

    expect(
      screen.getByText("We couldn't load the portfolio content."),
    ).toBeInTheDocument();
  });
});

describe('global-error.tsx', () => {
  it('owns its own <html> and <body> and uses only approved bilingual safe text', () => {
    // jsdom's React client renderer unwraps <html>/<body>, so the self-contained
    // document structure is asserted from server HTML.
    const html = renderToStaticMarkup(
      <GlobalError
        error={new Error('connect ECONNREFUSED db-prod-1.internal')}
        reset={() => {}}
      />,
    );
    const doc = new DOMParser().parseFromString(html, 'text/html');

    expect(html).toMatch(/^<html[ >]/);
    expect(doc.querySelector('html')).not.toBeNull();
    expect(doc.querySelector('body')).not.toBeNull();

    const text = doc.body.textContent ?? '';
    expect(text).toContain('No pudimos cargar el portfolio.');
    expect(text).toContain("We couldn't load the portfolio.");
    expect(text).not.toContain('ECONNREFUSED');
    expect(text).not.toContain('db-prod-1');
    expect(text).not.toMatch(/Trabajo|Experiencia|Proyectos|Contacto/);
  });

  it('recovers through the reset control', () => {
    const reset = vi.fn();

    render(<GlobalError error={new Error('boom')} reset={reset} />);
    fireEvent.click(screen.getByRole('button', {name: /Reintentar|Retry/}));

    expect(reset).toHaveBeenCalledTimes(1);
  });
});

describe('not-found.tsx', () => {
  it('uses the exact Spanish 404 copy and a valid localized return link', async () => {
    notFoundHarness.locale = 'es';

    render(await NotFound());

    expect(
      screen.getByRole('heading', {name: 'Página no encontrada'}),
    ).toBeInTheDocument();
    expect(
      screen.getByText('La página solicitada no existe.'),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('link', {name: 'Volver al portfolio'}),
    ).toHaveAttribute('href', '/es');
  });

  it('uses the exact English 404 copy under the English locale', async () => {
    notFoundHarness.locale = 'en';

    render(await NotFound());

    expect(
      screen.getByRole('heading', {name: 'Page not found'}),
    ).toBeInTheDocument();
    expect(
      screen.getByText('The requested page does not exist.'),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('link', {name: 'Back to portfolio'}),
    ).toHaveAttribute('href', '/en');
  });
});
