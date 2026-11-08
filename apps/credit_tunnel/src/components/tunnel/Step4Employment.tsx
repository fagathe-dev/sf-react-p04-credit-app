import { ChoiceChips } from '../ui/ChoiceChips';
import { MonthYearField } from './dateFields';
import { SelectField } from './fields';
import { employmentOptions, PAST_YEARS, professionOptions, YES_NO_OPTIONS, type StepProps } from './stepProps';
import { hasTrialPeriod, STATUSES_WITHOUT_CONTRACT } from './tunnelForm';

/** Étape 4 : situation professionnelle de l'emprunteur (celle du co-emprunteur : étape 4 bis). */
export const Step4Employment = ({ values, errors, onChange }: StepProps) => {
  const needsContract = values.employmentStatus !== '' && !STATUSES_WITHOUT_CONTRACT.includes(values.employmentStatus);
  const asksTrialPeriod = hasTrialPeriod(values.employmentStatus);

  return (
    <>
      <div className="field-row">
        <SelectField
          label="Situation professionnelle"
          value={values.employmentStatus}
          error={errors.employmentStatus}
          options={employmentOptions}
          onChange={(employmentStatus) => onChange({ employmentStatus })}
        />
        <SelectField
          label="Catégorie socio-professionnelle"
          value={values.professionCategory}
          error={errors.professionCategory}
          options={professionOptions}
          onChange={(professionCategory) => onChange({ professionCategory })}
        />
      </div>
      {needsContract || asksTrialPeriod ? (
        <div className="field-row">
          {needsContract ? (
            <MonthYearField
              label="Début du contrat ou de l'activité"
              value={values.contractStartDate}
              error={errors.contractStartDate}
              hint="Votre ancienneté est prise en compte par nos partenaires."
              {...PAST_YEARS}
              onChange={(contractStartDate) => onChange({ contractStartDate })}
            />
          ) : null}
          {asksTrialPeriod ? (
            <ChoiceChips
              label="Période d'essai terminée ?"
              value={values.trialPeriodCompleted}
              error={errors.trialPeriodCompleted}
              options={YES_NO_OPTIONS}
              onChange={(trialPeriodCompleted) => onChange({ trialPeriodCompleted })}
            />
          ) : null}
        </div>
      ) : null}
    </>
  );
};
