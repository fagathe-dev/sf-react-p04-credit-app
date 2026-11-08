import { useState } from 'react';
import { Chip } from '../ui/Chip';
import { MoneyField } from './fields';
import type { StepProps } from './stepProps';
import type { TunnelField } from './tunnelForm';

type OptionalCharge = Extract<TunnelField, 'consumerLoans' | 'alimonyPaid' | 'otherCharges'>;

const OPTIONAL_CHARGES: Array<{ field: OptionalCharge; label: string; hint?: string }> = [
  { field: 'consumerLoans', label: 'Crédits en cours', hint: 'Total des mensualités de vos crédits à la consommation.' },
  { field: 'alimonyPaid', label: 'Pensions versées' },
  { field: 'otherCharges', label: 'Autres charges' },
];

/**
 * Étape 6 : charges mensuelles. Même fonctionnement que l'étape 5 :
 * la charge de logement selon la situation (loyer ou mensualité de crédit immobilier),
 * puis des chips pour les autres charges, chacune faisant apparaître son champ.
 */
export const Step6Charges = ({ values, errors, onChange }: StepProps) => {
  // Une ligne déjà renseignée (retour en arrière, brouillon) reste affichée
  const [enabled, setEnabled] = useState<OptionalCharge[]>(() => OPTIONAL_CHARGES.filter(({ field }) => values[field] !== '').map(({ field }) => field));

  const toggle = (field: OptionalCharge) => {
    if (enabled.includes(field)) {
      setEnabled((current) => current.filter((item) => item !== field));
      onChange({ [field]: '' });
    } else {
      setEnabled((current) => [...current, field]);
    }
  };

  const shown = OPTIONAL_CHARGES.filter(({ field }) => enabled.includes(field));
  const isTenant = values.housingStatus === 'TENANT';
  const hasMortgage = values.housingStatus === 'OWNER_WITH_MORTGAGE';

  return (
    <>
      {isTenant || hasMortgage ? (
        <div className="field-row">
          {isTenant ? <MoneyField label="Loyer mensuel" value={values.rent} error={errors.rent} onChange={(rent) => onChange({ rent })} /> : null}
          {hasMortgage ? (
            <MoneyField
              label="Mensualité de crédit immobilier"
              value={values.mortgage}
              error={errors.mortgage}
              onChange={(mortgage) => onChange({ mortgage })}
            />
          ) : null}
        </div>
      ) : (
        <p className="muted">Votre situation de logement ne comporte ni loyer ni crédit immobilier.</p>
      )}

      <div className="field">
        <span className="field-label" id="other-charges-label">
          Autres charges mensuelles
        </span>
        <div className="chip-group" role="group" aria-labelledby="other-charges-label">
          {OPTIONAL_CHARGES.map(({ field, label }) => (
            <Chip key={field} selected={enabled.includes(field)} onToggle={() => toggle(field)}>
              {label}
            </Chip>
          ))}
        </div>
        <p className="field-hint">Sélectionnez les charges qui vous concernent : un champ apparaît pour chacune.</p>
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
