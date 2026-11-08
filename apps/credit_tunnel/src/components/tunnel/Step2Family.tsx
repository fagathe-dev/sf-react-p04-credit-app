import { MARITAL_STATUS_LABELS } from 'credit-front-core';
import { ChoiceChips } from '../ui/ChoiceChips';
import { SelectField } from './fields';
import { toOptions, YES_NO_OPTIONS, type StepProps } from './stepProps';

export const Step2Family = ({ values, errors, onChange }: StepProps) => (
  <>
    <div className="field-row">
      <SelectField
        label="Situation familiale"
        value={values.maritalStatus}
        error={errors.maritalStatus}
        options={toOptions(MARITAL_STATUS_LABELS)}
        onChange={(maritalStatus) => onChange({ maritalStatus })}
      />
      <SelectField
        label="Enfants à charge"
        value={values.dependentChildrenCount}
        error={errors.dependentChildrenCount}
        hint="250 € de reste à vivre supplémentaire par enfant."
        options={Array.from({ length: 9 }, (_, count) => ({ value: String(count), label: count === 8 ? '8 ou plus' : String(count) }))}
        onChange={(dependentChildrenCount) => onChange({ dependentChildrenCount })}
      />
    </div>
    <ChoiceChips
      label="Souhaitez-vous emprunter avec un co-emprunteur ?"
      value={values.hasCoBorrower ? 'yes' : 'no'}
      options={YES_NO_OPTIONS}
      hint={
        values.hasCoBorrower
          ? 'Sa situation professionnelle et son identité vous seront demandées aux étapes 4 bis et 7 bis. Le reste à vivre minimal passe à 1 100 €.'
          : 'Les revenus d\'un co-emprunteur sont pris en compte dans l\'étude de votre demande.'
      }
      onChange={(answer) => onChange({ hasCoBorrower: answer === 'yes' })}
    />
  </>
);
