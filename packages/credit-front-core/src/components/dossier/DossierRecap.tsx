import type { ReactNode } from 'react';
import type { DossierRequestSnapshot } from '../../types/credit.types';
import {
  formatPersonName,
  EMPLOYMENT_STATUS_LABELS,
  HOUSING_STATUS_LABELS,
  MARITAL_STATUS_LABELS,
  PROFESSION_CATEGORY_LABELS,
  PROJECT_LABELS,
} from '../../types/enums';
import { formatDate, formatPhone } from '../../utils/dateFormatter';
import { formatCentsToEuros } from '../../utils/moneyFormatter';

interface DossierRecapProps {
  request: DossierRequestSnapshot;
  /** Masque l'identité et le contact (déjà affichés ailleurs, ex. fiche conseiller). */
  hideIdentity?: boolean;
}

type Row = [label: string, value: ReactNode];

const money = (cents: number | undefined) => formatCentsToEuros(cents ?? 0);

/** Période d'essai : libellé, ou chaîne vide si non concerné (anciens dossiers, non salariés). */
const trialLabel = (completed: boolean | null | undefined): string => (completed === true ? 'Terminée' : completed === false ? 'En cours' : '');

/** « Mme Marie Durand (née Martin) » quand un nom de naissance est renseigné. */
const withBirthName = (name: string, birthName: string | null | undefined): string => (birthName ? `${name} (née ${birthName})` : name);

const RecapSection = ({ title, rows }: { title: string; rows: Row[] }) => (
  <section className="recap-section">
    <h3>{title}</h3>
    <dl>
      {rows.map(([label, value]) => (
        <div key={label}>
          <dt>{label}</dt>
          <dd>{value}</dd>
        </div>
      ))}
    </dl>
  </section>
);

/**
 * Récapitulatif des données déclarées dans le tunnel (dossier figé au moment du résultat).
 */
export const DossierRecap = ({ request, hideIdentity = false }: DossierRecapProps) => {
  const { incomes, charges, coBorrower } = request;

  const incomeRows: Row[] = [['Salaire net mensuel', money(incomes.netMonthlySalaryCents)]];
  if (request.hasCoBorrower) incomeRows.push(['Salaire du co-emprunteur', money(incomes.coBorrowerNetSalaryCents)]);
  if (incomes.familyAllowancesCents > 0) incomeRows.push(['Allocations', money(incomes.familyAllowancesCents)]);
  if (incomes.pensionsReceivedCents > 0) incomeRows.push(['Pensions perçues', money(incomes.pensionsReceivedCents)]);
  if (incomes.rentalIncomeGrossCents > 0) incomeRows.push(['Revenus locatifs bruts (retenus à 70 %)', money(incomes.rentalIncomeGrossCents)]);
  if (incomes.otherMonthlyIncomeCents > 0) incomeRows.push(['Autres revenus', money(incomes.otherMonthlyIncomeCents)]);

  const chargeRows: Row[] = [];
  if (charges.rentMonthlyCents > 0) chargeRows.push(['Loyer', money(charges.rentMonthlyCents)]);
  if (charges.mortgageMonthlyCents > 0) chargeRows.push(['Prêt immobilier', money(charges.mortgageMonthlyCents)]);
  if (charges.consumerLoansMonthlyCents > 0) chargeRows.push(['Crédits en cours', money(charges.consumerLoansMonthlyCents)]);
  if (charges.alimonyPaidMonthlyCents > 0) chargeRows.push(['Pensions versées', money(charges.alimonyPaidMonthlyCents)]);
  if (charges.otherChargesMonthlyCents > 0) chargeRows.push(['Autres charges', money(charges.otherChargesMonthlyCents)]);
  if (chargeRows.length === 0) chargeRows.push(['Charges mensuelles', 'Aucune']);

  const employmentRows: Row[] = [
    ['Situation', EMPLOYMENT_STATUS_LABELS[request.employmentStatus]],
    ['Catégorie', PROFESSION_CATEGORY_LABELS[request.professionCategory]],
    ['Début du contrat', formatDate(request.contractStartDate)],
  ];
  if (trialLabel(request.trialPeriodCompleted)) employmentRows.push(['Période d\'essai', trialLabel(request.trialPeriodCompleted)]);
  if (request.hasCoBorrower && coBorrower) {
    employmentRows.push(['Co-emprunteur', `${EMPLOYMENT_STATUS_LABELS[coBorrower.employmentStatus]} · ${PROFESSION_CATEGORY_LABELS[coBorrower.professionCategory]}`]);
    if (trialLabel(coBorrower.trialPeriodCompleted)) employmentRows.push(['Période d\'essai du co-emprunteur', trialLabel(coBorrower.trialPeriodCompleted)]);
    employmentRows.push(['Identité du co-emprunteur', `${withBirthName(formatPersonName(coBorrower.civility, coBorrower.firstName, coBorrower.lastName) || '—', coBorrower.birthName)} · né(e) le ${formatDate(coBorrower.birthDate)}`]);
  }

  return (
    <div className="dossier-recap">
      <RecapSection
        title="Projet"
        rows={[
          ['Type de projet', PROJECT_LABELS[request.projectType]],
          ['Montant demandé', formatCentsToEuros(request.requestedAmountCents, { withCents: false })],
          ['Durée', `${request.durationMonths} mois`],
        ]}
      />
      <RecapSection
        title="Foyer et logement"
        rows={[
          ['Situation familiale', MARITAL_STATUS_LABELS[request.maritalStatus]],
          ['Emprunt à deux', request.hasCoBorrower ? 'Oui' : 'Non'],
          ['Enfants à charge', String(request.dependentChildrenCount)],
          ['Logement', HOUSING_STATUS_LABELS[request.housingStatus]],
          ['Commune', `${request.postalCode} ${request.city}`],
        ]}
      />
      <RecapSection title="Emploi" rows={employmentRows} />
      <RecapSection title="Revenus mensuels" rows={incomeRows} />
      <RecapSection title="Charges mensuelles" rows={chargeRows} />
      {hideIdentity ? null : (
        <RecapSection
          title="Identité et contact"
          rows={[
            ['Nom', withBirthName(formatPersonName(request.civility, request.firstName, request.lastName), request.birthName)],
            ['Date de naissance', formatDate(request.birthDate)],
            ['E-mail', request.email],
            ['Mobile', formatPhone(request.phone)],
          ]}
        />
      )}
    </div>
  );
};
