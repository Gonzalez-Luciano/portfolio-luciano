import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import {NextIntlClientProvider} from 'next-intl';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {ApiStatus} from './api-status';

const englishMessages = {
  Foundation: {
    apiStatusLabel: 'API status',
    checkApi: 'Check API',
    checkingApi: 'Checking API...',
    apiAvailable: 'API available: ok/{version}',
    apiUnavailable: 'API unavailable.',
  },
};

const spanishMessages = {
  Foundation: {
    apiStatusLabel: 'Estado de la API',
    checkApi: 'Comprobar API',
    checkingApi: 'Comprobando la API...',
    apiAvailable: 'API disponible: ok/{version}',
    apiUnavailable: 'La API no estÃ¡ disponible.',
  },
};

function renderApiStatus(messages = englishMessages) {
  return render(
    <NextIntlClientProvider locale="en" messages={messages}>
      <ApiStatus />
    </NextIntlClientProvider>,
  );
}

describe('ApiStatus', () => {
  afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
  });

  it('does not make an API request before the user chooses to check it', () => {
    const fetchImpl = vi.fn();
    vi.stubGlobal('fetch', fetchImpl);

    renderApiStatus();

    expect(fetchImpl).not.toHaveBeenCalled();
  });

  it('shows the API version after an explicit same-origin check', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(
      new Response(JSON.stringify({data: {status: 'ok', version: 'v1'}}), {
        headers: {'content-type': 'application/json'},
      }),
    );
    vi.stubGlobal('fetch', fetchImpl);

    renderApiStatus();
    fireEvent.click(screen.getByRole('button', {name: 'Check API'}));

    expect(await screen.findByRole('status')).toHaveTextContent(
      'API available: ok/v1',
    );
    expect(fetchImpl).toHaveBeenCalledWith('/api/v1', {
      headers: {accept: 'application/json'},
    });
  });

  it('shows a safe error when the API cannot be reached', async () => {
    vi.stubGlobal(
      'fetch',
      vi
        .fn()
        .mockRejectedValue(new TypeError('connect ECONNREFUSED http://api')),
    );

    renderApiStatus();
    fireEvent.click(screen.getByRole('button', {name: 'Check API'}));

    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('API unavailable.');
    });
    expect(screen.getByRole('status')).not.toHaveTextContent('http://api');
  });

  it('shows a safe error when the version endpoint returns a malformed success payload', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({data: {status: 'ready', version: 'v1'}}), {
          headers: {'content-type': 'application/json'},
        }),
      ),
    );

    renderApiStatus();
    fireEvent.click(screen.getByRole('button', {name: 'Check API'}));

    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('API unavailable.');
    });
  });

  it('uses the locale message set for visible API status strings', () => {
    renderApiStatus(spanishMessages);

    expect(
      screen.getByRole('region', {name: 'Estado de la API'}),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {name: 'Comprobar API'}),
    ).toBeInTheDocument();
  });
});
