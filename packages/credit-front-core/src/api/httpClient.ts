import { ApiError } from 'core-ts';

/**
 * Client fetch typé vers credit_api : cookies de session inclus (credentials: 'include'),
 * erreurs HTTP converties en ApiError (core-ts) avec le corps JSON de l'API.
 */

/** Corps d'erreur renvoyé par credit_api. */
export interface ApiErrorBody {
  message?: string;
  code?: string;
  /** Erreurs 422 : champ du payload (chemin pointé) → message. */
  violations?: Record<string, string>;
}

export type CreditApiError = ApiError<ApiErrorBody>;

export const HTTP_UNAUTHORIZED = 401;
export const HTTP_NOT_FOUND = 404;
export const HTTP_UNPROCESSABLE = 422;
export const HTTP_TOO_MANY_REQUESTS = 429;

export const isApiError = (error: unknown): error is CreditApiError => error instanceof ApiError;

export const isUnauthorized = (error: unknown): boolean => isApiError(error) && error.status === HTTP_UNAUTHORIZED;

export const isNotFound = (error: unknown): boolean => isApiError(error) && error.status === HTTP_NOT_FOUND;

export const isValidationError = (error: unknown): boolean => isApiError(error) && error.status === HTTP_UNPROCESSABLE;

export const isTooManyRequests = (error: unknown): boolean => isApiError(error) && error.status === HTTP_TOO_MANY_REQUESTS;

/** Violations d'une erreur 422 (vide sinon). */
export const getViolations = (error: unknown): Record<string, string> =>
  isValidationError(error) ? ((error as CreditApiError).data?.violations ?? {}) : {};

/** Message lisible pour l'utilisateur, quelle que soit l'erreur. */
export const getErrorMessage = (error: unknown, fallback = 'Une erreur est survenue. Veuillez réessayer.'): string => {
  if (isApiError(error)) {
    return error.data?.message ?? fallback;
  }
  if (error instanceof TypeError) {
    return 'Le service est momentanément injoignable. Vérifiez votre connexion et réessayez.';
  }

  return fallback;
};

export interface HttpClient {
  get<T>(path: string, query?: Record<string, string | number | undefined | null>): Promise<T>;
  post<T>(path: string, body?: unknown): Promise<T>;
  patch<T>(path: string, body?: unknown): Promise<T>;
}

const parseBody = async (response: Response): Promise<unknown> => {
  if (response.status === 204) return null;

  const text = await response.text();
  if (text === '') return null;

  try {
    return JSON.parse(text) as unknown;
  } catch {
    return text;
  }
};

export const createHttpClient = (baseUrl: string): HttpClient => {
  const root = baseUrl.replace(/\/+$/, '');

  const request = async <T>(method: string, path: string, body?: unknown): Promise<T> => {
    const response = await fetch(`${root}${path}`, {
      method,
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });

    const data = await parseBody(response);

    if (!response.ok) {
      const errorBody = (typeof data === 'object' && data !== null ? data : { message: undefined }) as ApiErrorBody;
      throw new ApiError<ApiErrorBody>(response.status, response.statusText, errorBody.message ?? `Erreur HTTP ${response.status}`, response, errorBody);
    }

    return data as T;
  };

  return {
    get: <T>(path: string, query?: Record<string, string | number | undefined | null>) => {
      const params = new URLSearchParams();
      Object.entries(query ?? {}).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') params.set(key, String(value));
      });
      const search = params.toString();

      return request<T>('GET', search === '' ? path : `${path}?${search}`);
    },
    post: <T>(path: string, body?: unknown) => request<T>('POST', path, body ?? {}),
    patch: <T>(path: string, body?: unknown) => request<T>('PATCH', path, body ?? {}),
  };
};
