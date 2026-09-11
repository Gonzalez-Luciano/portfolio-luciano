import {afterEach, describe, expect, it, vi} from 'vitest';
import {requestPublicResource} from './client';

type Widget = {id: string};

function isWidget(value: unknown): value is Widget {
  return (
    typeof value === 'object' &&
    value !== null &&
    !Array.isArray(value) &&
    typeof (value as Record<string, unknown>).id === 'string'
  );
}

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(typeof body === 'string' ? body : JSON.stringify(body), {
    status,
    headers: {'content-type': 'application/json'},
  });
}

const ORIGIN = 'http://api';

function withOrigin(value = ORIGIN): void {
  vi.stubEnv('INTERNAL_API_ORIGIN', value);
}

describe('requestPublicResource', () => {
  afterEach(() => {
    vi.unstubAllEnvs();
    vi.unstubAllGlobals();
  });

  it('performs exactly one localized no-store GET with an accept header and a timeout signal', async () => {
    withOrigin();
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(
        jsonResponse({data: {id: 'a'}, meta: {generated_at: 'now'}}),
      );

    const result = await requestPublicResource('profile', 'es', isWidget, {
      fetchImpl,
    });

    expect(result).toEqual({ok: true, data: {id: 'a'}});
    expect(fetchImpl).toHaveBeenCalledTimes(1);
    expect(fetchImpl).toHaveBeenCalledWith(
      'http://api/api/v1/es/profile',
      expect.objectContaining({
        method: 'GET',
        cache: 'no-store',
        headers: {accept: 'application/json'},
      }),
    );
    const init = fetchImpl.mock.calls[0][1] as RequestInit;
    expect(init.signal).toBeInstanceOf(AbortSignal);
  });

  it('accepts a success envelope that carries an unconsumed top-level member', async () => {
    withOrigin();
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(jsonResponse({data: {id: 'b'}, meta: {page: 1}}));

    await expect(
      requestPublicResource('technologies', 'en', isWidget, {fetchImpl}),
    ).resolves.toEqual({ok: true, data: {id: 'b'}});
  });

  it('treats a success envelope without data as malformed', async () => {
    withOrigin();
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(jsonResponse({meta: {page: 1}}));

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'malformed', status: 200},
    });
  });

  it('treats a body carrying both data and error as malformed', async () => {
    withOrigin();
    const fetchImpl = vi.fn().mockResolvedValue(
      jsonResponse({
        data: {id: 'a'},
        error: {code: 'x', message: 'y', details: {}},
      }),
    );

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'malformed', status: 200},
    });
  });

  it('treats a 2xx error envelope as malformed', async () => {
    withOrigin();
    const fetchImpl = vi.fn().mockResolvedValue(
      jsonResponse({
        error: {code: 'not_found', message: 'nope', details: {}},
      }),
    );

    await expect(
      requestPublicResource('site', 'es', isWidget, {fetchImpl}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'site', kind: 'malformed', status: 200},
    });
  });

  it('treats a non-JSON body as malformed', async () => {
    withOrigin();
    const fetchImpl = vi.fn().mockResolvedValue(
      new Response('<html>upstream</html>', {
        status: 200,
        headers: {'content-type': 'text/html'},
      }),
    );

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'malformed', status: 200},
    });
  });

  it('treats data that the validator rejects as malformed', async () => {
    withOrigin();
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse({data: {id: 42}}));

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'malformed', status: 200},
    });
  });

  it('classifies a non-2xx response as an http failure with its status and no message', async () => {
    withOrigin();
    const fetchImpl = vi.fn().mockResolvedValue(
      jsonResponse(
        {
          error: {
            code: 'not_found',
            message: 'The requested API resource was not found.',
            details: {},
          },
        },
        404,
      ),
    );

    const result = await requestPublicResource('projects', 'es', isWidget, {
      fetchImpl,
    });

    expect(result).toEqual({
      ok: false,
      failure: {endpoint: 'projects', kind: 'http', status: 404},
    });
    expect(fetchImpl).toHaveBeenCalledTimes(1);
  });

  it('classifies a non-2xx response as http even when its body is a valid success envelope', async () => {
    withOrigin();
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(jsonResponse({data: {id: 'a'}}, 503));

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'http', status: 503},
    });
  });

  it('reports missing server configuration without attempting a request', async () => {
    withOrigin('');
    const fetchImpl = vi.fn();

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'configuration'},
    });
    expect(fetchImpl).not.toHaveBeenCalled();
  });

  it('classifies a fetch rejection as a network failure with no leaked detail', async () => {
    withOrigin();
    const fetchImpl = vi
      .fn()
      .mockRejectedValue(new TypeError('connect ECONNREFUSED http://api'));

    const result = await requestPublicResource('profile', 'es', isWidget, {
      fetchImpl,
    });

    expect(result).toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'network'},
    });
    expect(fetchImpl).toHaveBeenCalledTimes(1);
  });

  it('classifies the defensive timeout abort as a network failure', async () => {
    withOrigin();
    const signal = AbortSignal.timeout(1);
    const fetchImpl = vi.fn(
      (_input: string | URL | Request, init?: RequestInit): Promise<Response> =>
        new Promise((_resolve, reject) => {
          init?.signal?.addEventListener('abort', () => {
            reject((init.signal as AbortSignal).reason);
          });
        }),
    );

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl, signal}),
    ).resolves.toEqual({
      ok: false,
      failure: {endpoint: 'profile', kind: 'network'},
    });
  });

  it('does not swallow an abort that did not originate from its own timeout', async () => {
    withOrigin();
    const aborted = new DOMException('Aborted by caller.', 'AbortError');
    const fetchImpl = vi.fn().mockRejectedValue(aborted);

    await expect(
      requestPublicResource('profile', 'es', isWidget, {fetchImpl}),
    ).rejects.toBe(aborted);
  });

  it('never issues a second request after a failure', async () => {
    withOrigin();
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse({data: {id: 42}}));

    await requestPublicResource('profile', 'es', isWidget, {fetchImpl});

    expect(fetchImpl).toHaveBeenCalledTimes(1);
  });

  it('lets an unexpected validator exception propagate to the error boundary', async () => {
    withOrigin();
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(jsonResponse({data: {id: 'a'}}));
    const boom = new Error('validator bug');
    const throwingValidator = (value: unknown): value is Widget => {
      void value;
      throw boom;
    };

    await expect(
      requestPublicResource('profile', 'es', throwingValidator, {fetchImpl}),
    ).rejects.toBe(boom);
  });
});
