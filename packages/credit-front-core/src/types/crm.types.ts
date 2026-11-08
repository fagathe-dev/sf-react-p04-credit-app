/**
 * DTO du back-office conseiller (credit_api, /api/v1/crm).
 */
import type { CallbackPriority, CallbackStatus, CreditProjectType, DossierWorkflowStatus, EmailNotificationStatus, ScoringDecision } from './enums';
import type { DossierDetail } from './client.types';

export interface AdvisorProfile {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  lastLoginAt: string | null;
}

export interface CrmCallbackItem {
  dossierId: string;
  submittedAt: string;
  clientName: string;
  email: string;
  phone: string;
  projectType: CreditProjectType;
  requestedAmountCents: number;
  durationMonths: number;
  decision: ScoringDecision;
  callbackStatus: CallbackStatus | null;
  callbackUpdatedAt: string | null;
  advisorName: string | null;
  priority: CallbackPriority;
  /** Ce que le conseiller doit proposer (motif de la règle RG appliquée). */
  priorityReason: string;
  workflowStatus: DossierWorkflowStatus;
  debtRatioAfterBps: number;
}

export interface Paginated<T> {
  items: T[];
  page: number;
  limit: number;
  total: number;
  pages: number;
}

/** Filtre de priorité : '' = file d'appels (P1 à P3), 'ALL' = tous les dossiers. */
export type PriorityFilter = CallbackPriority | 'ALL' | '';

export interface CallbackFilters {
  priority?: PriorityFilter;
  decision?: ScoringDecision | '';
  callbackStatus?: CallbackStatus | '';
  workflowStatus?: DossierWorkflowStatus | '';
  page?: number;
  limit?: number;
}

export interface EmailNotification {
  type: string;
  label: string;
  subject: string;
  recipient: string;
  status: EmailNotificationStatus;
  sentAt: string;
}

export interface CrmDossierDetail {
  dossier: DossierDetail;
  clientName: string;
  email: string;
  phone: string;
  callbackStatus: CallbackStatus | null;
  advisorNote: string | null;
  callbackUpdatedAt: string | null;
  advisorName: string | null;
  acquisition: Record<string, string | null> | null;
  priority: CallbackPriority;
  priorityReason: string;
  /** Statuts que le conseiller peut appliquer (validation ou refus d'une offre choisie). */
  workflowTransitions: DossierWorkflowStatus[];
  emails: EmailNotification[];
}

export interface CallbackFollowUpPayload {
  callbackStatus: CallbackStatus;
  advisorNote: string | null;
  workflowStatus?: DossierWorkflowStatus | null;
}
