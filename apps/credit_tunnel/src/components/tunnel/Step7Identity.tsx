import { ChoiceChips } from '../ui/ChoiceChips';
import { DateSelectField } from './dateFields';
import { TextField } from './fields';
import { BIRTH_YEARS, civilityOptions, type StepProps } from './stepProps';
import { needsBirthName } from './tunnelForm';

/** Étape 7 : identité de l'emprunteur (celle du co-emprunteur : étape 7 bis). */
export const Step7Identity = ({ values, errors, onChange }: StepProps) => {
  const asksBirthName = needsBirthName(values.civility, values.maritalStatus);

  return (
    <>
      <div className="field-row">
        <ChoiceChips label="Civilité" value={values.civility} error={errors.civility} options={civilityOptions} onChange={(civility) => onChange({ civility })} />
        <DateSelectField
          label="Date de naissance"
          value={values.birthDate}
          error={errors.birthDate}
          hint="Vous devez avoir entre 18 ans révolus et 80 ans."
          {...BIRTH_YEARS}
          onChange={(birthDate) => onChange({ birthDate })}
        />
      </div>
      <div className="field-row">
        <TextField label="Prénom" value={values.firstName} error={errors.firstName} autoComplete="given-name" onChange={(firstName) => onChange({ firstName })} />
        <TextField
          label={asksBirthName ? "Nom d'usage" : 'Nom'}
          value={values.lastName}
          error={errors.lastName}
          autoComplete="family-name"
          onChange={(lastName) => onChange({ lastName })}
        />
      </div>
      {asksBirthName ? (
        <div className="field-row">
          <TextField
            label="Nom de naissance"
            value={values.birthName}
            error={errors.birthName}
            hint="Votre nom de jeune fille, tel qu'il figure sur votre acte de naissance."
            onChange={(birthName) => onChange({ birthName })}
          />
        </div>
      ) : null}
    </>
  );
};
