import type { OtpVerification } from '../types/client.types';
import type { AdvisorProfile, CallbackFilters, CallbackFollowUpPayload, CrmCallbackItem, CrmDossierDetail, Paginated } from '../types/crm.types';
import type { HttpClient } from './httpClient';

/** Back-office conseiller : /api/v1/crm */
export const createCrmApi = (http: HttpClient) => ({
  requestOtp: (email: string) => http.post<null>('/api/v1/crm/auth/otp/request', { email }),
  verifyOtp: (email: string, code: string) => http.post<OtpVerification>('/api/v1/crm/auth/otp/verify', { email, code }),
  logout: () => http.post<null>('/api/v1/crm/auth/logout'),
  me: () => http.get<AdvisorProfile>('/api/v1/crm/me'),
  callbacks: (filters: CallbackFilters = {}) =>
    http.get<Paginated<CrmCallbackItem>>('/api/v1/crm/callbacks', {
      priority: filters.priority,
      decision: filters.decision,
      callbackStatus: filters.callbackStatus,
      workflowStatus: filters.workflowStatus,
      page: filters.page,
      limit: filters.limit,
    }),
  dossier: (id: string) => http.get<CrmDossierDetail>(`/api/v1/crm/dossiers/${encodeURIComponent(id)}`),
  updateCallback: (id: string, payload: CallbackFollowUpPayload) =>
    http.patch<CrmDossierDetail>(`/api/v1/crm/dossiers/${encodeURIComponent(id)}/callback`, payload),
});

export type CrmApi = ReturnType<typeof createCrmApi>;
