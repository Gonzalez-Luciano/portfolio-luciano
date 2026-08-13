import type {ApiError, ApiResult, ApiSuccess} from './types';

export type ApiPayloadValidator<T> = (value: unknown) => value is T;

export type ApiRequestOptions<T = unknown> = RequestInit & {
  fetchImpl?: typeof fetch;
  validateData?: ApiPayloadValidator<T>;
};

type ApiClientErrorOptions = {
  status?: number;
  code?: string;
  details?: Record<string, string[]>;
};

export class ApiClientError extends Error {
  readonly status?: number;
  readonly code?: string;
  readonly details?: Record<string, string[]>;

  constructor(
    message: string,
    {status, code, details}: ApiClientErrorOptions = {},
  ) {
    super(message);
    this.name = 'ApiClientError';
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isErrorDetails(value: unknown): value is Record<string, string[]> {
  return (
    isRecord(value) &&
    Object.values(value).every(
      (messages) =>
        Array.isArray(messages) &&
        messages.every((message) => typeof message === 'string'),
    )
  );
}

function isApiError(value: unknown): value is ApiError {
  if (!isRecord(value) || !isRecord(value.error)) {
    return false;
  }

  const {code, message, details} = value.error;

  return (
    typeof code === 'string' &&
    typeof message === 'string' &&
    isErrorDetails(details)
  );
}

function parseEnvelope<T>(
  value: unknown,
  status: number,
  validateData?: ApiPayloadValidator<T>,
): ApiResult<T> {
  if (!isRecord(value)) {
    throw new ApiClientError('The API returned an unexpected response.', {
      status,
    });
  }

  const hasData = Object.hasOwn(value, 'data');
  const hasError = Object.hasOwn(value, 'error');

  if (hasData === hasError || Object.keys(value).length !== 1) {
    throw new ApiClientError('The API returned an unexpected response.', {
      status,
    });
  }

  if (hasData) {
    if (validateData && !validateData(value.data)) {
      throw new ApiClientError('The API returned an unexpected response.', {
        status,
      });
    }

    return {data: value.data as T};
  }

  if (!isApiError(value)) {
    throw new ApiClientError('The API returned an unexpected response.', {
      status,
    });
  }

  return value;
}

function requireServerOrigin(origin: string | undefined): string {
  if (!origin) {
    throw new ApiClientError('The API is not configured.');
  }

  return origin.replace(/\/$/, '');
}

function apiBase(): string {
  return typeof window === 'undefined'
    ? `${requireServerOrigin(process.env.INTERNAL_API_ORIGIN)}/api`
    : '/api';
}

export async function requestApi<T>(
  path: `/${string}`,
  {
    fetchImpl = fetch,
    headers,
    validateData,
    ...init
  }: ApiRequestOptions<T> = {},
): Promise<ApiSuccess<T>> {
  let response: Response;
  const url = `${apiBase()}${path}`;

  try {
    response = await fetchImpl(url, {
      ...init,
      headers: {accept: 'application/json', ...headers},
    });
  } catch {
    throw new ApiClientError('Unable to reach the API.');
  }

  let body: unknown;

  try {
    body = await response.json();
  } catch {
    throw new ApiClientError('The API returned an unexpected response.', {
      status: response.status,
    });
  }

  const envelope = parseEnvelope<T>(body, response.status, validateData);

  if ('error' in envelope) {
    throw new ApiClientError(envelope.error.message, {
      status: response.status,
      code: envelope.error.code,
      details: envelope.error.details,
    });
  }

  if (!response.ok) {
    throw new ApiClientError('The API returned an unexpected response.', {
      status: response.status,
    });
  }

  return envelope;
}
