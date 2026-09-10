import type {Locale} from '@/i18n/routing';
import type {
  EndpointFailure,
  EndpointFailureKind,
  EndpointName,
  EndpointResult,
} from './types';

/**
 * Server-only transport for the Phase 5 public portfolio site.
 *
 * It performs exactly one localized `GET` against the internal Phase 4 API,
 * never caches, applies an eight-second defensive timeout, and converts every
 * known operational failure into a safe, diagnostic-only {@link EndpointResult}
 * rather than throwing. Programmer errors — most notably a validator that
 * throws — are left to propagate to the App Router error boundary.
 *
 * This module reads `INTERNAL_API_ORIGIN` and builds absolute internal URLs.
 * It must never be imported into a Client Component.
 */

const REQUEST_TIMEOUT_MS = 8_000;

/**
 * Narrow, test-only seams. Production callers pass nothing; the six fetchers
 * forward whatever a test injects so the network and timeout branches can be
 * exercised deterministically without real sockets or an eight-second wait.
 */
export type PublicRequestTestOptions = {
  /** Replace the real `fetch` implementation. */
  fetchImpl?: typeof fetch;
  /**
   * Replace the internally created `AbortSignal.timeout(8000)`. Used only to
   * drive the timeout-abort branch deterministically.
   */
  signal?: AbortSignal;
};

type PublicRequestValidator<T> = (value: unknown) => value is T;

function serverOrigin(): string | undefined {
  const origin = process.env.INTERNAL_API_ORIGIN;

  if (typeof origin !== 'string' || origin.trim() === '') {
    return undefined;
  }

  return origin.replace(/\/$/, '');
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isAbortError(error: unknown): boolean {
  return (
    error instanceof DOMException &&
    (error.name === 'AbortError' || error.name === 'TimeoutError')
  );
}

function toFailure(
  endpoint: EndpointName,
  kind: EndpointFailureKind,
  status?: number,
): {ok: false; failure: EndpointFailure} {
  return {
    ok: false,
    failure:
      status === undefined ? {endpoint, kind} : {endpoint, kind, status},
  };
}

export async function requestPublicResource<T>(
  endpoint: EndpointName,
  locale: Locale,
  validator: PublicRequestValidator<T>,
  options: PublicRequestTestOptions = {},
): Promise<EndpointResult<T>> {
  const origin = serverOrigin();

  if (origin === undefined) {
    return toFailure(endpoint, 'configuration');
  }

  const {fetchImpl = fetch, signal = AbortSignal.timeout(REQUEST_TIMEOUT_MS)} =
    options;
  const url = `${origin}/api/v1/${locale}/${endpoint}`;

  let response: Response;

  try {
    response = await fetchImpl(url, {
      method: 'GET',
      cache: 'no-store',
      headers: {accept: 'application/json'},
      signal,
    });
  } catch (error) {
    if (isAbortError(error) && !signal.aborted) {
      // An abort that did not come from our own timeout is unexpected; surface
      // it instead of mislabelling it as a network failure.
      throw error;
    }

    return toFailure(endpoint, 'network');
  }

  if (!response.ok) {
    return toFailure(endpoint, 'http', response.status);
  }

  let body: unknown;

  try {
    body = await response.json();
  } catch {
    return toFailure(endpoint, 'malformed', response.status);
  }

  if (
    !isRecord(body) ||
    !Object.hasOwn(body, 'data') ||
    Object.hasOwn(body, 'error')
  ) {
    return toFailure(endpoint, 'malformed', response.status);
  }

  const data: unknown = body.data;

  // A validator exception is a bug, not an operational failure: it must reach
  // the App Router error boundary rather than become a `malformed` result.
  if (!validator(data)) {
    return toFailure(endpoint, 'malformed', response.status);
  }

  return {ok: true, data};
}
