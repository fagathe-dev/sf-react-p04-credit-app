import { StorageService } from 'core-ts';
import { CreditProjectType, type AcquisitionInput } from 'credit-front-core';

/**
 * Préremplissage du tunnel : paramètres d'URL (site vitrine credit_front et son simulateur /simulateur) + sessionStorage.
 *
 * ?project=green_loan&amount=15000&duration=60&step=2&income=2500&utm_source=…
 * (montants en euros entiers dans l'URL, convertis en centimes à l'envoi).
 */
export interface TunnelPrefill {
  project?: CreditProjectType;
  /** Euros */
  amount?: number;
  duration?: number;
  /** Revenu net mensuel saisi dans le simulateur du site vitrine, en euros. */
  income?: number;
  step?: number;
}

const storage = new StorageService('session');

const PREFILL_KEY = 'credit_tunnel.prefill';
const ACQUISITION_KEY = 'credit_tunnel.acquisition';
const DRAFT_KEY = 'credit_tunnel.draft';

const PROJECTS = Object.values(CreditProjectType) as string[];

const positiveInt = (value: string | null): number | undefined => {
  if (value === null || !/^\d{1,7}$/.test(value)) return undefined;
  const parsed = Number(value);

  return parsed > 0 ? parsed : undefined;
};

const readPrefillFromUrl = (params: URLSearchParams): TunnelPrefill => {
  const project = params.get('project');

  return {
    project: project !== null && PROJECTS.includes(project) ? (project as CreditProjectType) : undefined,
    amount: positiveInt(params.get('amount')),
    duration: positiveInt(params.get('duration')),
    income: positiveInt(params.get('income')),
    step: positiveInt(params.get('step')),
  };
};

const readAcquisitionFromUrl = (params: URLSearchParams): AcquisitionInput | null => {
  const value = (name: string) => {
    const raw = params.get(name)?.trim();
    return raw ? raw.slice(0, 255) : null;
  };
  const acquisition: AcquisitionInput = {
    utmSource: value('utm_source'),
    utmMedium: value('utm_medium'),
    utmCampaign: value('utm_campaign'),
    referrer: document.referrer ? document.referrer.slice(0, 1024) : null,
  };

  return acquisition.utmSource || acquisition.utmMedium || acquisition.utmCampaign || acquisition.referrer ? acquisition : null;
};

const compact = <T extends object>(value: T): Partial<T> =>
  Object.fromEntries(Object.entries(value).filter(([, v]) => v !== undefined)) as Partial<T>;

/**
 * Lit l'URL, la fusionne avec le préremplissage déjà en session (l'URL l'emporte) et mémorise le tout.
 */
export const loadPrefill = (search: string = window.location.search): TunnelPrefill => {
  const params = new URLSearchParams(search);
  const stored = (storage.get(PREFILL_KEY) as TunnelPrefill | null) ?? {};
  const prefill = { ...stored, ...compact(readPrefillFromUrl(params)) };
  storage.set(PREFILL_KEY, prefill);

  const acquisition = readAcquisitionFromUrl(params);
  if (acquisition) storage.set(ACQUISITION_KEY, acquisition);

  return prefill;
};

export const readAcquisition = (): AcquisitionInput | null => (storage.get(ACQUISITION_KEY) as AcquisitionInput | null) ?? null;

/** Brouillon du tunnel (étape et saisies) : conservé le temps de l'onglet. */
export const readDraft = <T>(): T | null => (storage.get(DRAFT_KEY) as T | null) ?? null;

export const saveDraft = <T>(draft: T): void => {
  storage.set(DRAFT_KEY, draft);
};

/** Après l'envoi : la demande est enregistrée dans l'espace client, plus rien à garder ici. */
export const clearTunnelStorage = (): void => {
  storage.remove(DRAFT_KEY);
  storage.remove(PREFILL_KEY);
};
