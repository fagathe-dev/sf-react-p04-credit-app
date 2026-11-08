/**
 * Payload du tunnel et DTO de résultat (miroir de packages/credit-core/src/DTO).
 * Montants en centimes, taux en points de base : jamais de nombre décimal.
 */
import type {
  AdjustmentKind,
  Civility,
  ClientStatus,
  CreditProjectType,
  EmploymentStatus,
  HousingStatus,
  MaritalStatus,
  ProfessionCategory,
  RiskGrade,
  RuleSeverity,
  ScoringDecision,
} from './enums';

export interface IncomesInput {
  netMonthlySalaryCents: number;
  coBorrowerNetSalaryCents: number;
  familyAllowancesCents: number;
  pensionsReceivedCents: number;
  /** Revenus locatifs bruts : retenus à 70 % par le scoring. */
  rentalIncomeGrossCents: number;
  otherMonthlyIncomeCents: number;
}

export interface ChargesInput {
  rentMonthlyCents: number;
  mortgageMonthlyCents: number;
  consumerLoansMonthlyCents: number;
  alimonyPaidMonthlyCents: number;
  otherChargesMonthlyCents: number;
}

export interface CoBorrowerInput {
  employmentStatus: EmploymentStatus;
  professionCategory: ProfessionCategory;
  contractStartDate: string | null;
  civility: Civility | null;
  firstName: string | null;
  lastName: string | null;
  /** AAAA-MM-JJ */
  birthDate: string;
  /** Période d'essai terminée (salariés), null si non concerné. */
  trialPeriodCompleted: boolean | null;
  /** Nom de naissance (femme mariée) ; absent des dossiers antérieurs à ce champ. */
  birthName?: string | null;
}

export interface AcquisitionInput {
  utmSource: string | null;
  utmMedium: string | null;
  utmCampaign: string | null;
  referrer: string | null;
}

/** Corps de POST /api/v1/compare : les 8 étapes, consentements et acquisition. */
export interface ComparisonRequest {
  projectType: CreditProjectType;
  requestedAmountCents: number;
  durationMonths: number;
  maritalStatus: MaritalStatus;
  hasCoBorrower: boolean;
  dependentChildrenCount: number;
  housingStatus: HousingStatus;
  housingSince: string | null;
  postalCode: string;
  city: string;
  employmentStatus: EmploymentStatus;
  professionCategory: ProfessionCategory;
  contractStartDate: string | null;
  /** Période d'essai terminée (salariés), null si non concerné. */
  trialPeriodCompleted: boolean | null;
  coBorrower: CoBorrowerInput | null;
  incomes: IncomesInput;
  charges: ChargesInput;
  civility: Civility;
  firstName: string;
  lastName: string;
  /** Nom de naissance : obligatoire pour une femme mariée (Mme + MARRIED), sinon null. Absent des dossiers antérieurs. */
  birthName?: string | null;
  birthDate: string;
  email: string;
  phone: string;
  consentPartnerTransmission: boolean;
  consentAdvisorCallback: boolean;
  consentClientSpaceCreation: boolean;
  acquisition: AcquisitionInput | null;
}

/** Récapitulatif enregistré dans le dossier : la demande sans consentements ni acquisition. */
export type DossierRequestSnapshot = Omit<
  ComparisonRequest,
  'consentPartnerTransmission' | 'consentAdvisorCallback' | 'consentClientSpaceCreation' | 'acquisition'
>;

/** Réponse de POST /api/v1/compare. */
export interface ComparisonSubmission {
  clientStatus: ClientStatus;
  dossierId: string;
}

export interface QuoteOfferDto {
  partnerCode: string;
  partnerName: string;
  partnerLogo: string;
  offerCode: string;
  productName: string;
  projectType: CreditProjectType;
  amountCents: number;
  durationMonths: number;
  nominalRateBps: number;
  aprBps: number;
  monthlyInstallmentCents: number;
  monthlyInsuranceCents: number;
  totalInterestCents: number;
  totalFinancingCostCents: number;
  debtRatioAfterBps: number;
  isBestRate: boolean;
}

export interface ScoreContributionDto {
  criterion: string;
  label: string;
  points: number;
}

export interface ScoringSummaryDto {
  score: number;
  riskGrade: RiskGrade;
  weightedIncomeCents: number;
  existingChargesCents: number;
  debtRatioBeforeBps: number;
  debtRatioAfterBps: number;
  referenceMonthlyInstallmentCents: number;
  remainderToLiveCents: number;
  minRemainderToLiveCents: number;
  referenceOfferCode: string | null;
  scoreBreakdown: ScoreContributionDto[];
}

export interface RuleExecutionDto {
  ruleCode: string;
  passed: boolean;
  severity: RuleSeverity;
  message: string;
  offerCode: string | null;
  field: string | null;
}

export interface AdjustmentSuggestionDto {
  kind: AdjustmentKind;
  label: string;
  partnerCode: string;
  partnerName: string;
  partnerLogo: string;
  offerCode: string;
  productName: string;
  amountCents: number;
  durationMonths: number;
  monthlyInstallmentCents: number;
  aprBps: number;
  debtRatioAfterBps: number;
  distanceBps: number;
}

export interface AdvisorFallbackDto {
  reasons: string[];
  suggestions: AdjustmentSuggestionDto[];
  callbackRequested: boolean;
  phone: string | null;
  message: string;
}

export interface RejectionDto {
  reasons: string[];
}

/** Résultat d'un dossier, tel qu'affiché par QuoteResultsView. */
export interface DossierResult {
  decision: ScoringDecision;
  scoringSummary: ScoringSummaryDto;
  quotes: QuoteOfferDto[];
  advisorFallback: AdvisorFallbackDto | null;
  rejection: RejectionDto | null;
}

/** GET /api/v1/catalog */
export interface CatalogOffer {
  offerCode: string;
  productName: string;
  projectType: CreditProjectType;
  nominalRateBps: number;
  aprBps: number;
  insuranceRateBps: number;
  minAmountCents: number;
  maxAmountCents: number;
  minDurationMonths: number;
  maxDurationMonths: number;
  representativeExample: {
    amountCents: number;
    durationMonths: number;
    monthlyInstallmentCents: number;
    totalFinancingCostCents: number;
  };
}

export interface CatalogPartner {
  code: string;
  name: string;
  logo: string;
  offers: CatalogOffer[];
}

export interface CatalogResponse {
  partners: CatalogPartner[];
}
