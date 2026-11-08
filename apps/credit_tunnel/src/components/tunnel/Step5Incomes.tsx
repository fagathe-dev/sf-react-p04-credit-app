import { useState } from 'react';
import { Chip } from '../ui/Chip';
import { MoneyField } from './fields';
import type { StepProps } from './stepProps';
import type { TunnelField } from './tunnelForm';

type OptionalIncome = Extract<TunnelField, 'familyAllowances' | 'pensionsReceived' | 'rentalIncomeGross' | 'otherMonthlyIncome'>;

const OPTIONAL_INCOMES: Array<{ field: OptionalIncome; label: string; hint?: string }> = [
  { field: 'familyAllowances', label: 'Allocations (CAF, APL)' },
  { field: 'pensionsReceived', label: 'Pensions perçues' },
  { field: 'rentalIncomeGross', label: 'Revenus locatifs bruts', hint: 'Pris en compte à 70 % (abattement bancaire forfaitaire de 30 %).' },
  { field: 'otherMonthlyIncome', label: 'Autres revenus' },
];

/** Revenus du foyer : salaires (emprunteur et, pour un emprunt à deux, co-emprunteur) et revenus annexes. */
export const Step5Incomes = ({ values, errors, onChange }: StepProps) => {
  // Une ligne déjà renseignée (retour en arrière, brouillon) reste affichée
  const [enabled, setEnabled] = useState<OptionalIncome[]>(() => OPTIONAL_INCOMES.filter(({ field }) => values[field] !== '').map(({ field }) => field));

  const toggle = (field: OptionalIncome) => {
    if (enabled.includes(field)) {
      setEnabled((current) => current.filter((item) => item !== field));
      onChange({ [field]: '' });
    } else {
      setEnabled((current) => [...current, field]);
    }
  };

  const shown = OPTIONAL_INCOMES.filter(({ field }) => enabled.includes(field));

  return (
    <>
      <div className="field-row">
        <MoneyField
          label="Votre salaire net mensuel (ou retraite)"
          value={values.netMonthlySalary}
          error={errors.netMonthlySalary}
          onChange={(netMonthlySalary) => onChange({ netMonthlySalary })}
        />
        {values.hasCoBorrower ? (
          <MoneyField
            label="Salaire net mensuel du co-emprunteur"
            value={values.coBorrowerNetSalary}
            error={errors.coBorrowerNetSalary}
            onChange={(coBorrowerNetSalary) => onChange({ coBorrowerNetSalary })}
          />
        ) : null}
      </div>

      <div className="field">
        <span className="field-label" id="other-incomes-label">
          Autres revenus mensuels
        </span>
        <div className="chip-group" role="group" aria-labelledby="other-incomes-label">
          {OPTIONAL_INCOMES.map(({ field, label }) => (
            <Chip key={field} selected={enabled.includes(field)} onToggle={() => toggle(field)}>
              {label}
            </Chip>
          ))}
        </div>
        <p className="field-hint">Sélectionnez les revenus qui vous concernent : un champ apparaît pour chacun.</p>
      </div>

      {shown.length > 0 ? (
        <div className="field-row">
          {shown.map(({ field, label, hint }) => (
            <MoneyField key={field} label={label} hint={hint} value={values[field]} error={errors[field]} onChange={(value) => onChange({ [field]: value })} />
          ))}
        </div>
      ) : null}
    </>
  );
};
