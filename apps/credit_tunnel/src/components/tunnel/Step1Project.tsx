import { PROJECT_LABELS, ProjectIcon, type CreditProjectType } from 'credit-front-core';
import { ChoiceCards, MoneyField, SelectField } from './fields';
import type { StepProps } from './stepProps';
import { DURATION_OPTIONS } from './tunnelForm';

const PROJECT_DESCRIPTIONS: Record<CreditProjectType, string> = {
  green_loan: 'Rénovation énergétique, véhicule propre',
  auto: 'Voiture neuve ou d\'occasion',
  home_improvement: 'Rénovation, aménagement',
  personal_goods: 'Équipement, mobilier',
  cash_treasury: 'Projet libre, imprévu',
};

/** Durées proposées, complétées par une durée préremplie hors liste (ex. ?duration=30). */
const durationOptions = (current: string) => {
  const months = new Set<number>(DURATION_OPTIONS);
  if (/^\d{1,3}$/.test(current) && Number(current) >= 6 && Number(current) <= 120) months.add(Number(current));

  return [...months].sort((a, b) => a - b).map((value) => ({ value: String(value), label: `${value} mois` }));
};

export const Step1Project =({ values, errors, onChange }: StepProps) => (
  <>
    <ChoiceCards
      label="Quel est votre projet ?"
      value={values.projectType}
      error={errors.projectType}
      columns={5}
      onChange={(projectType) => onChange({ projectType })}
      options={(Object.keys(PROJECT_LABELS) as CreditProjectType[]).map((project) => ({
        value: project,
        label: PROJECT_LABELS[project],
        description: PROJECT_DESCRIPTIONS[project],
        icon: <ProjectIcon project={project} />,
      }))}
    />
    <div className="field-row">
      <MoneyField label="Montant souhaité" value={values.amount} error={errors.amount} hint="De 300 € à 100 000 €" onChange={(amount) => onChange({ amount })} />
      <SelectField
        label="Durée de remboursement"
        value={values.durationMonths}
        error={errors.durationMonths}
        onChange={(durationMonths) => onChange({ durationMonths })}
        options={durationOptions(values.durationMonths)}
      />
    </div>
  </>
);
