import {afterEach, describe, expect, it, vi} from 'vitest';
import {ApiClientError, requestApi} from './client';

type VersionData = {
  status: 'ok';
  version: 'v1';
};

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: {'content-type': 'application/json'},
  });
}

describe('requestApi', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    vi.unstubAllEnvs();
  });

  it('returns an exact success envelope from a browser-relative request', async () => {
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(jsonResponse({data: {status: 'ok', version: 'v1'}}));

    await expect(requestApi<VersionData>('/v1', {fetchImpl})).resolves.toEqual({
      data: {status: 'ok', version: 'v1'},
    });
    expect(fetchImpl).toHaveBeenCalledWith('/api/v1', {
      headers: {accept: 'application/json'},
    });
  });

  it('preserves a handled API error with its HTTP status', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(
      jsonResponse(
        {
          error: {
            code: 'validation_failed',
            message: 'The submitted data is invalid.',
            details: {email: ['The email field is required.']},
          },
        },
        422,
      ),
    );

    await expect(
      requestApi<VersionData>('/v1', {fetchImpl}),
    ).rejects.toMatchObject({
      name: 'ApiClientError',
      status: 422,
      code: 'validation_failed',
      details: {email: ['The email field is required.']},
      message: 'The submitted data is invalid.',
    });
  });

  it('propagates an HTTP failure even when its body is a success envelope', async () => {
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(
        jsonResponse({data: {status: 'ok', version: 'v1'}}, 503),
      );

    await expect(
      requestApi<VersionData>('/v1', {fetchImpl}),
    ).rejects.toMatchObject({
      name: 'ApiClientError',
      status: 503,
      message: 'The API returned an unexpected response.',
    });
  });

  it('converts a network rejection into a safe client error', async () => {
    const fetchImpl = vi
      .fn()
      .mockRejectedValue(new TypeError('connect ECONNREFUSED http://api'));

    await expect(
      requestApi<VersionData>('/v1', {fetchImpl}),
    ).rejects.toMatchObject({
      name: 'ApiClientError',
      message: 'Unable to reach the API.',
      status: undefined,
    });
  });

  it.each([
    ['a non-JSON response', new Response('<html>upstream error</html>')],
    ['a response with neither envelope member', jsonResponse({status: 'ok'})],
    [
      'a response with both envelope members',
      jsonResponse({
        data: {status: 'ok'},
        error: {code: 'bad', message: 'Bad', details: {}},
      }),
    ],
    [
      'a response with an unexpected top-level member',
      jsonResponse({data: {status: 'ok'}, meta: {page: 1}}),
    ],
    [
      'a response with invalid error detail fields',
      jsonResponse({
        error: {code: 'bad', message: 'Bad', details: {email: 'not-an-array'}},
      }),
    ],
  ])(
    'rejects %s instead of accepting a malformed contract',
    async (_description, response) => {
      const fetchImpl = vi.fn().mockResolvedValue(response);

      await expect(
        requestApi<VersionData>('/v1', {fetchImpl}),
      ).rejects.toBeInstanceOf(ApiClientError);
    },
  );

  it('uses the internal server origin only when a request is explicitly made at runtime', async () => {
    vi.stubGlobal('window', undefined);
    vi.stubEnv('INTERNAL_API_ORIGIN', 'http://api');
    const fetchImpl = vi
      .fn()
      .mockResolvedValue(jsonResponse({data: {status: 'ok', version: 'v1'}}));

    await requestApi<VersionData>('/v1', {fetchImpl});

    expect(fetchImpl).toHaveBeenCalledWith('http://api/api/v1', {
      headers: {accept: 'application/json'},
    });
  });

  it('reports missing server configuration without attempting a network request', async () => {
    vi.stubGlobal('window', undefined);
    vi.stubEnv('INTERNAL_API_ORIGIN', '');
    const fetchImpl = vi.fn();

    await expect(
      requestApi<VersionData>('/v1', {fetchImpl}),
    ).rejects.toMatchObject({
      name: 'ApiClientError',
      message: 'The API is not configured.',
    });
    expect(fetchImpl).not.toHaveBeenCalled();
  });
});
