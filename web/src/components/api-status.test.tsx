import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {ApiStatus} from './api-status';

describe('ApiStatus', () => {
  afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
  });

  it('does not make an API request before the user chooses to check it', () => {
    const fetchImpl = vi.fn();
    vi.stubGlobal('fetch', fetchImpl);

    render(<ApiStatus />);

    expect(fetchImpl).not.toHaveBeenCalled();
  });

  it('shows the API version after an explicit same-origin check', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(
      new Response(JSON.stringify({data: {status: 'ok', version: 'v1'}}), {
        headers: {'content-type': 'application/json'},
      }),
    );
    vi.stubGlobal('fetch', fetchImpl);

    render(<ApiStatus />);
    fireEvent.click(screen.getByRole('button', {name: 'Check API'}));

    expect(await screen.findByRole('status')).toHaveTextContent('ok/v1');
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

    render(<ApiStatus />);
    fireEvent.click(screen.getByRole('button', {name: 'Check API'}));

    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('API unavailable.');
    });
    expect(screen.getByRole('status')).not.toHaveTextContent('http://api');
  });
});
