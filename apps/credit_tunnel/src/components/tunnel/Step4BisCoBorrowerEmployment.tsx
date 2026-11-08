import { ChoiceChips } from '../ui/ChoiceChips';
import { MonthYearField } from './dateFields';
import { SelectField } from './fields';
import { employmentOptions, PAST_YEARS, professionOptions, YES_NO_OPTIONS, type StepProps } from './stepProps';
import { hasTrialPeriod, STATUSES_WITHOUT_CONTRACT } from './tunnelForm';

/** Étape 4 bis : situation professionnelle du co-emprunteur (emprunt à deux uniquement). */
export const Step4BisCoBorrowerEmployment = ({ values, errors, onChange }: StepProps) => {
  const needsContract = values.coEmploymentStatus !== '' && !STATUSES_WITHOUT_CONTRACT.includes(values.coEmploymentStatus);
  const asksTrialPeriod = hasTrialPeriod(values.coEmploymentStatus);

  return (
    <>
      <p className="muted">Le co-emprunteur s'engage avec vous : sa situation professionnelle est prise en compte dans l'étude.</p>
      <div className="field-row">
        <SelectField
          label="Situation professionnelle"
          value={values.coEmploymentStatus}
          error={errors.coEmploymentStatus}
          options={employmentOptions}
          onChange={(coEmploymentStatus) => onChange({ coEmploymentStatus })}
        />
        <SelectField
          label="Catégorie socio-professionnelle"
          value={values.coProfessionCategory}
          error={errors.coProfessionCategory}
          options={professionOptions}
          onChange={(coProfessionCategory) => onChange({ coProfessionCategory })}
        />
      </div>
      {needsContract || asksTrialPeriod ? (
        <div className="field-row">
          {needsContract ? (
            <MonthYearField
              label="Début du contrat ou de l'activité"
              value={values.coContractStartDate}
              error={errors.coContractStartDate}
              {...PAST_YEARS}
              onChange={(coContractStartDate) => onChange({ coContractStartDate })}
            />
          ) : null}
          {asksTrialPeriod ? (
            <ChoiceChips
              label="Période d'essai terminée ?"
              value={values.coTrialPeriodCompleted}
              error={errors.coTrialPeriodCompleted}
              options={YES_NO_OPTIONS}
              onChange={(coTrialPeriodCompleted) => onChange({ coTrialPeriodCompleted })}
            />
          ) : null}
        </div>
      ) : null}
    </>
  );
};
