import { ChoiceChips } from '../ui/ChoiceChips';
import { DateSelectField } from './dateFields';
import { TextField } from './fields';
import { BIRTH_YEARS, civilityOptions, type StepProps } from './stepProps';
import { needsBirthName } from './tunnelForm';

/** Étape 7 bis : identité du co-emprunteur (emprunt à deux uniquement). */
export const Step7BisCoBorrowerIdentity = ({ values, errors, onChange }: StepProps) => {
  const asksBirthName = needsBirthName(values.coCivility, values.maritalStatus);

  return (
    <>
      <div className="field-row">
        <ChoiceChips label="Civilité" value={values.coCivility} error={errors.coCivility} options={civilityOptions} onChange={(coCivility) => onChange({ coCivility })} />
        <DateSelectField
          label="Date de naissance"
          value={values.coBirthDate}
          error={errors.coBirthDate}
          hint="Le co-emprunteur doit avoir entre 18 ans révolus et 80 ans."
          {...BIRTH_YEARS}
          onChange={(coBirthDate) => onChange({ coBirthDate })}
        />
      </div>
      <div className="field-row">
        <TextField label="Prénom" value={values.coFirstName} error={errors.coFirstName} onChange={(coFirstName) => onChange({ coFirstName })} />
        <TextField label={asksBirthName ? "Nom d'usage" : 'Nom'} value={values.coLastName} error={errors.coLastName} onChange={(coLastName) => onChange({ coLastName })} />
      </div>
      {asksBirthName ? (
        <div className="field-row">
          <TextField
            label="Nom de naissance"
            value={values.coBirthName}
            error={errors.coBirthName}
            hint="Son nom de jeune fille, tel qu'il figure sur son acte de naissance."
            onChange={(coBirthName) => onChange({ coBirthName })}
          />
        </div>
      ) : null}
    </>
  );
};
