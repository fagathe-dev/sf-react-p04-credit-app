import { CheckIcon } from 'credit-front-core';

export interface StepperStep {
  id: string;
  label: string;
  /** Numéro affiché dans la pastille (ex. « 4 », « 4 bis ») ; par défaut : rang dans la liste. */
  number?: string;
  /** Étape secondaire (bis) : pastille réduite. */
  isSecondary?: boolean;
}

interface StepperProps {
  steps: StepperStep[];
  /** Index de l'étape courante. */
  current: number;
  /** Retour à une étape déjà validée (les étapes suivantes ne sont pas cliquables). */
  onStepClick?: (index: number) => void;
  label?: string;
}

const Indicator = ({ number }: { number: string }) => {
  const [main, suffix] = number.split(' ');

  return (
    <>
      {main}
      {suffix ? <sup className="stepper-suffix">{suffix}</sup> : null}
    </>
  );
};

/**
 * Indicateur d'étapes : pastilles numérotées reliées par un trait, libellés sous les pastilles.
 * Étape courante : rôle primary ; étapes validées : coche, cliquables ; étapes à venir : neutres.
 * En petit écran, seuls les pastilles et le résumé de l'étape courante sont affichés.
 */
export const Stepper = ({ steps, current, onStepClick, label = 'Étapes du formulaire' }: StepperProps) => {
  const currentStep = steps[current];

  return (
    <nav className="stepper" aria-label={label}>
      <ol className="stepper-list">
        {steps.map((step, index) => {
          const number = step.number ?? String(index + 1);
          const state = index < current ? 'is-complete' : index === current ? 'is-current' : 'is-upcoming';
          const content = (
            <>
              <span className="stepper-indicator" aria-hidden="true">
                {index < current ? <CheckIcon width={18} height={18} /> : <Indicator number={number} />}
              </span>
              <span className="stepper-label">
                <span className="visually-hidden">
                  Étape {number}
                  {index < current ? ' (terminée)' : ''} :{' '}
                </span>
                {step.label}
              </span>
            </>
          );

          return (
            <li
              key={step.id}
              className={`stepper-item ${state}${step.isSecondary ? ' is-secondary' : ''}`}
              aria-current={index === current ? 'step' : undefined}
            >
              {index < current && onStepClick ? (
                <button type="button" className="stepper-trigger" onClick={() => onStepClick(index)}>
                  {content}
                </button>
              ) : (
                <span className="stepper-trigger">{content}</span>
              )}
            </li>
          );
        })}
      </ol>
      {currentStep ? (
        <p className="stepper-summary" aria-hidden="true">
          Étape {currentStep.number ?? current + 1} · {currentStep.label}
        </p>
      ) : null}
    </nav>
  );
};
