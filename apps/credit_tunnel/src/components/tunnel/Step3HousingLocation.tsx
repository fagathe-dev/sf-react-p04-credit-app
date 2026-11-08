import { HOUSING_STATUS_LABELS } from 'credit-front-core';
import { MonthYearField } from './dateFields';
import { SelectField, TextField } from './fields';
import { PAST_YEARS, toOptions, type StepProps } from './stepProps';

export const Step3HousingLocation = ({ values, errors, onChange }: StepProps) => (
  <>
    <div className="field-row">
      <SelectField
        label="Votre logement"
        value={values.housingStatus}
        error={errors.housingStatus}
        options={toOptions(HOUSING_STATUS_LABELS)}
        onChange={(housingStatus) => onChange({ housingStatus })}
      />
      <MonthYearField
        label="Date d'entrée (facultatif)"
        value={values.housingSince}
        error={errors.housingSince}
        {...PAST_YEARS}
        onChange={(housingSince) => onChange({ housingSince })}
      />
    </div>
    <div className="field-row">
      <TextField
        label="Code postal"
        value={values.postalCode}
        error={errors.postalCode}
        inputMode="numeric"
        autoComplete="postal-code"
        maxLength={5}
        hint="France métropolitaine et Corse uniquement."
        onChange={(postalCode) => onChange({ postalCode: postalCode.replace(/\D/g, '').slice(0, 5) })}
      />
      <TextField label="Ville" value={values.city} error={errors.city} autoComplete="address-level2" onChange={(city) => onChange({ city })} />
    </div>
  </>
);
