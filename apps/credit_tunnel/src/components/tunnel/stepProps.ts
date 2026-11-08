import { CIVILITY_LABELS, EMPLOYMENT_STATUS_LABELS, PROFESSION_CATEGORY_LABELS } from 'credit-front-core';
import type { FieldErrors, TunnelFormState } from './tunnelForm';

export interface StepProps {
  values: TunnelFormState;
  errors: FieldErrors;
  onChange: (patch: Partial<TunnelFormState>) => void;
}

/** Options d'un objet de libellés (enums de credit-front-core) pour SelectField / ChoiceChips. */
export const toOptions = <T extends string>(labels: Record<T, string>): Array<{ value: T; label: string }> =>
  (Object.entries(labels) as Array<[T, string]>).map(([value, label]) => ({ value, label }));

/** Options partagées par les étapes de l'emprunteur et du co-emprunteur. */
export const civilityOptions = toOptions(CIVILITY_LABELS);
export const employmentOptions = toOptions(EMPLOYMENT_STATUS_LABELS);
export const professionOptions = toOptions(PROFESSION_CATEGORY_LABELS);

export type YesNo = 'yes' | 'no';
export const YES_NO_OPTIONS: Array<{ value: YesNo; label: string }> = [
  { value: 'yes', label: 'Oui' },
  { value: 'no', label: 'Non' },
];

const currentYear = new Date().getFullYear();

/** Années proposées pour une date de naissance : de 18 à 81 ans (la règle d'âge 18–80 ans est validée ensuite). */
export const BIRTH_YEARS = { latestYear: currentYear - 18, earliestYear: currentYear - 81 };
/** Années proposées pour une date d'entrée dans le logement ou un début de contrat. */
export const PAST_YEARS = { latestYear: currentYear, earliestYear: currentYear - 70 };
