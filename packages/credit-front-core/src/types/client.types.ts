/**
 * DTO de l'espace client (credit_api, /api/v1/auth et /api/v1/me).
 */
import type { Civility, CreditProjectType, DossierWorkflowStatus, ScoringDecision } from './enums';
import type { DossierRequestSnapshot, DossierResult } from './credit.types';

export interface ConsentState {
  clientSpaceCreatedAt: string;
  partnerTransmissionAt: string | null;
  advisorCallbackAt: string | null;
  advisorCallback: boolean;
}

export interface ClientProfile {
  id: string;
  email: string;
  civility: Civility;
  firstName: string;
  lastName: string;
  birthDate: string;
  phone: string;
  createdAt: string;
  lastLoginAt: string | null;
  consents: ConsentState;
}

export interface DossierSummary {
  id: string;
  /** Date du résultat (ISO 8601). */
  submittedAt: string;
  projectType: CreditProjectType;
  requestedAmountCents: number;
  durationMonths: number;
  decision: ScoringDecision;
  quotesCount: number;
  workflowStatus: DossierWorkflowStatus;
  /** Fin de validité des offres (résultat + 7 jours). */
  quotesExpireAt: string;
}

export interface DossierDetail extends DossierResult {
  id: string;
  submittedAt: string;
  request: DossierRequestSnapshot;
  /** Consentement de rappel en vigueur (il peut avoir changé depuis le résultat). */
  consentAdvisorCallbackAt: string | null;
  phone: string;
  workflowStatus: DossierWorkflowStatus;
  quotesExpireAt: string;
  quotesExpired: boolean;
  subscribedOfferCode: string | null;
  subscribedAt: string | null;
}

/** Réponse de POST /api/v1/auth/otp/verify. */
export interface OtpVerification {
  dossierId?: string | null;
}
