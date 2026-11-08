import type { CatalogResponse, ComparisonRequest, ComparisonSubmission } from '../types/credit.types';
import type { HttpClient } from './httpClient';

/** POST /api/v1/compare, GET /api/v1/catalog */
export const createCreditApi = (http: HttpClient) => ({
  compare: (payload: ComparisonRequest) => http.post<ComparisonSubmission>('/api/v1/compare', payload),
  catalog: () => http.get<CatalogResponse>('/api/v1/catalog'),
});

export type CreditApi = ReturnType<typeof createCreditApi>;
