export type ApiSuccess<T> = {
  data: T;
};

export type ApiError = {
  error: {
    code: string;
    message: string;
    details: Record<string, string[]>;
  };
};

export type ApiResult<T> = ApiSuccess<T> | ApiError;
