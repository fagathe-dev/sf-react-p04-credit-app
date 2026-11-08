import type { ClientProfile, DossierDetail, DossierSummary, OtpVerification } from '../types/client.types';
import type { HttpClient } from './httpClient';

/** Espace client : /api/v1/auth et /api/v1/me */
export const createClientApi = (http: HttpClient) => ({
  /** Répond toujours 204, que le compte existe ou non. */
  requestOtp: (email: string, dossierId?: string | null) => http.post<null>('/api/v1/auth/otp/request', { email, dossierId: dossierId ?? null }),
  verifyOtp: (email: string, code: string) => http.post<OtpVerification>('/api/v1/auth/otp/verify', { email, code }),
  logout: () => http.post<null>('/api/v1/auth/logout'),
  me: () => http.get<ClientProfile>('/api/v1/me'),
  updateConsents: (consentAdvisorCallback: boolean) => http.patch<ClientProfile>('/api/v1/me/consents', { consentAdvisorCallback }),
  dossiers: () => http.get<DossierSummary[]>('/api/v1/me/dossiers'),
  dossier: (id: string) => http.get<DossierDetail>(`/api/v1/me/dossiers/${encodeURIComponent(id)}`),
  /** Choix d'une offre : le dossier passe en QUOTE_SUBSCRIBED (422 si offres expirées ou déjà choisies). */
  subscribe: (id: string, offerCode: string) => http.post<DossierDetail>(`/api/v1/me/dossiers/${encodeURIComponent(id)}/subscribe`, { offerCode }),
});

export type ClientApi = ReturnType<typeof createClientApi>;
