import { AlertIcon, ArrowLeftIcon, ArrowRightIcon, getErrorMessage, getViolations, isValidationError } from 'credit-front-core';
import { useEffect, useRef, useState, type ComponentType, type FormEvent } from 'react';
import { creditApi } from '../../config';
import { buildClientRedirect } from '../../utils/clientRedirect';
import { leaveWithoutPrompt, useLeaveGuard } from '../../utils/leaveGuard';
import { clearTunnelStorage, loadPrefill, readAcquisition, readDraft, saveDraft } from '../../utils/prefillStorage';
import { Stepper } from '../ui/Stepper';
import { Step1Project } from './Step1Project';
import { Step2Family } from './Step2Family';
import { Step3HousingLocation } from './Step3HousingLocation';
import { Step4Employment } from './Step4Employment';
import { Step5Incomes } from './Step5Incomes';
import { Step6Charges } from './Step6Charges';
import { Step7Identity } from './Step7Identity';
import { Step8ContactConsent } from './Step8ContactConsent';
import { Step4BisCoBorrowerEmployment } from './Step4BisCoBorrowerEmployment';
import { Step7BisCoBorrowerIdentity } from './Step7BisCoBorrowerIdentity';
import type { StepProps } from './stepProps';
import {
  initialTunnelState,
  MAIN_STEP_IDS,
  mapApiViolations,
  STEP_IDS,
  STEP_RULES,
  stepNumber,
  toPayload,
  visibleSteps,
  type FieldErrors,
  type StepId,
  type TunnelFormState,
} from './tunnelForm';

/**
 * Présentation des étapes. L'ordre, la visibilité et la validation sont dans STEP_RULES (tunnelForm.ts) :
 * ajouter une étape = un identifiant dans STEP_IDS, ses règles, et une entrée ici.
 */
const STEP_VIEWS: Record<StepId, { title: string; label: string; Component: ComponentType<StepProps> }> = {
  project: { title: 'Votre projet', label: 'Projet', Component: Step1Project },
  family: { title: 'Votre famille', label: 'Famille', Component: Step2Family },
  housing: { title: 'Votre logement', label: 'Logement', Component: Step3HousingLocation },
  employment: { title: 'Votre emploi', label: 'Emploi', Component: Step4Employment },
  employmentCoBorrower: { title: 'Emploi du co-emprunteur', label: 'Emploi co-emp.', Component: Step4BisCoBorrowerEmployment },
  incomes: { title: 'Vos revenus', label: 'Revenus', Component: Step5Incomes },
  charges: { title: 'Vos charges', label: 'Charges', Component: Step6Charges },
  identity: { title: 'Votre identité', label: 'Identité', Component: Step7Identity },
  identityCoBorrower: { title: 'Identité du co-emprunteur', label: 'Identité co-emp.', Component: Step7BisCoBorrowerIdentity },
  contact: { title: 'Contact et consentements', label: 'Contact', Component: Step8ContactConsent },
};

interface Draft {
  values: TunnelFormState;
  step: StepId;
}

const isStepId = (value: unknown): value is StepId => typeof value === 'string' && (STEP_IDS as readonly string[]).includes(value);

/** Première étape visible invalide avant `target` : on ne saute pas une étape incomplète. */
const firstReachableStep = (values: TunnelFormState, target: StepId): StepId => {
  for (const id of visibleSteps(values)) {
    if (id === target) return id;
    if (Object.keys(STEP_RULES[id].validate(values)).length > 0) return id;
  }

  return target;
};

const initialDraft = (): Draft => {
  const prefill = loadPrefill();
  const draft = readDraft<{ values?: Partial<TunnelFormState>; step?: unknown }>();
  const values: TunnelFormState = { ...initialTunnelState, ...(draft?.values ?? {}) };

  // Les paramètres d'URL (site vitrine et son simulateur) l'emportent sur le brouillon
  if (prefill.project) values.projectType = prefill.project;
  if (prefill.amount) values.amount = String(prefill.amount);
  if (prefill.duration) values.durationMonths = String(prefill.duration);
  if (prefill.income && values.netMonthlySalary === '') values.netMonthlySalary = String(prefill.income);

  // ?step=N (numérotation des étapes principales, 1 = projet) ou étape du brouillon
  const steps = visibleSteps(values);
  const requested: StepId = prefill.step
    ? MAIN_STEP_IDS[Math.min(MAIN_STEP_IDS.length, Math.max(1, prefill.step)) - 1]
    : isStepId(draft?.step)
      ? draft.step
      : 'project';

  return { values, step: firstReachableStep(values, steps.includes(requested) ? requested : 'project') };
};

export const ComparisonTunnel = () => {
  const [{ values, step }, setDraft] = useState<Draft>(initialDraft);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [formError, setFormError] = useState<string[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const headingRef = useRef<HTMLHeadingElement>(null);

  // Saisie commencée (y compris préremplie par le simulateur du site vitrine) : confirmation avant de quitter
  useLeaveGuard(step !== 'project' || JSON.stringify(values) !== JSON.stringify(initialTunnelState));

  useEffect(() => {
    saveDraft<Draft>({ values, step });
  }, [values, step]);

  useEffect(() => {
    headingRef.current?.focus();
  }, [step]);

  const steps = visibleSteps(values);
  const index = Math.max(0, steps.indexOf(step));
  const { title, Component } = STEP_VIEWS[step];
  const isLastStep = index === steps.length - 1;

  const onChange = (patch: Partial<TunnelFormState>) => {
    setDraft((current) => ({ ...current, values: { ...current.values, ...patch } }));
    // L'erreur d'un champ disparaît dès qu'il est modifié
    setErrors((current) => {
      const next = { ...current };
      (Object.keys(patch) as Array<keyof TunnelFormState>).forEach((field) => delete next[field]);
      return next;
    });
  };

  const goTo = (target: StepId) => {
    setFormError([]);
    setDraft((current) => ({ ...current, step: target }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const submit = async () => {
    setIsSubmitting(true);
    setFormError([]);
    try {
      const payload = toPayload(values, readAcquisition());
      const submission = await creditApi.compare(payload);
      clearTunnelStorage();
      // Demande enregistrée : plus rien à perdre, pas de confirmation de sortie
      leaveWithoutPrompt(buildClientRedirect(submission, payload.email));
    } catch (error) {
      if (isValidationError(error)) {
        const mapped = mapApiViolations(getViolations(error));
        setErrors(mapped.errors);
        setFormError(mapped.unmapped.length > 0 ? mapped.unmapped : [getErrorMessage(error)]);
        setDraft((current) => ({ ...current, step: mapped.step }));
      } else {
        setFormError([getErrorMessage(error)]);
      }
      setIsSubmitting(false);
    }
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const stepErrors = STEP_RULES[step].validate(values);
    setErrors(stepErrors);

    if (Object.keys(stepErrors).length > 0) {
      setFormError(['Certaines informations sont à compléter.']);
      return;
    }

    if (isLastStep) {
      void submit();
    } else {
      goTo(steps[index + 1]);
    }
  };

  return (
    <div className="tunnel">
      <Stepper
        steps={steps.map((id) => ({ id, label: STEP_VIEWS[id].label, number: stepNumber(id), isSecondary: STEP_RULES[id].bisOf !== undefined }))}
        current={index}
        onStepClick={(target) => goTo(steps[target])}
      />

      <form className="tunnel-card" onSubmit={handleSubmit} noValidate>
        <h1 ref={headingRef} tabIndex={-1}>
          {title}
        </h1>

        {formError.length > 0 ? (
          <div className="alert alert-danger" role="alert">
            <AlertIcon />
            <div>
              {formError.map((message) => (
                <p key={message}>{message}</p>
              ))}
            </div>
          </div>
        ) : null}

        <Component values={values} errors={errors} onChange={onChange} />

        <div className="tunnel-actions">
          {index > 0 ? (
            <button type="button" className="btn btn-outline" onClick={() => goTo(steps[index - 1])} disabled={isSubmitting}>
              <ArrowLeftIcon /> Précédent
            </button>
          ) : (
            <span />
          )}
          <button type="submit" className="btn btn-primary" disabled={isSubmitting}>
            {isLastStep ? (isSubmitting ? 'Analyse en cours…' : 'Voir mes offres') : 'Continuer'} <ArrowRightIcon />
          </button>
        </div>

        {isLastStep ? (
          <p className="space-notice small muted">
            En soumettant ce formulaire, j'accepte la création de mon espace client, dans lequel je retrouverai ma demande et son résultat. Je
            m'y connecte ensuite avec un code envoyé par e-mail.
          </p>
        ) : null}
      </form>

      <p className="legal-notice small">Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager.</p>
    </div>
  );
};
