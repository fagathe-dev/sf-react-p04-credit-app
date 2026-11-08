import {
  parseEurosToCents,
  type AcquisitionInput,
  type Civility,
  type ComparisonRequest,
  type CreditProjectType,
  type EmploymentStatus,
  type HousingStatus,
  type MaritalStatus,
  type ProfessionCategory,
} from 'credit-front-core';
import { isAllowedAge, isFrenchMobile, isMetropolitanPostalCode, isPastMonth, EMAIL, normalizeMobile } from '../../utils/validators';

/**
 * État saisi dans le tunnel : chaînes telles que tapées (euros, mois AAAA-MM),
 * converties en centimes et dates AAAA-MM-JJ par toPayload().
 */
export interface TunnelFormState {
  // Étape 1
  projectType: CreditProjectType | '';
  amount: string;
  durationMonths: string;
  // Étape 2
  maritalStatus: MaritalStatus | '';
  hasCoBorrower: boolean;
  dependentChildrenCount: string;
  // Étape 3
  housingStatus: HousingStatus | '';
  housingSince: string;
  postalCode: string;
  city: string;
  // Étape 4
  employmentStatus: EmploymentStatus | '';
  professionCategory: ProfessionCategory | '';
  contractStartDate: string;
  /** Période d'essai terminée : 'yes' | 'no' (salariés uniquement). */
  trialPeriodCompleted: 'yes' | 'no' | '';
  coEmploymentStatus: EmploymentStatus | '';
  coProfessionCategory: ProfessionCategory | '';
  coContractStartDate: string;
  coTrialPeriodCompleted: 'yes' | 'no' | '';
  // Étape 5
  netMonthlySalary: string;
  coBorrowerNetSalary: string;
  familyAllowances: string;
  pensionsReceived: string;
  rentalIncomeGross: string;
  otherMonthlyIncome: string;
  // Étape 6
  rent: string;
  mortgage: string;
  consumerLoans: string;
  alimonyPaid: string;
  otherCharges: string;
  // Étape 7
  civility: Civility | '';
  firstName: string;
  lastName: string;
  /** Nom de naissance : demandé si needsBirthName() (femme mariée). */
  birthName: string;
  birthDate: string;
  coCivility: Civility | '';
  coFirstName: string;
  coLastName: string;
  coBirthName: string;
  coBirthDate: string;
  // Étape 8
  email: string;
  phone: string;
  consentPartnerTransmission: boolean;
  consentAdvisorCallback: boolean;
}

export type TunnelField = keyof TunnelFormState;
export type FieldErrors = Partial<Record<TunnelField, string>>;

export const initialTunnelState: TunnelFormState = {
  projectType: '',
  amount: '',
  durationMonths: '',
  maritalStatus: '',
  hasCoBorrower: false,
  dependentChildrenCount: '0',
  housingStatus: '',
  housingSince: '',
  postalCode: '',
  city: '',
  employmentStatus: '',
  professionCategory: '',
  contractStartDate: '',
  trialPeriodCompleted: '',
  coEmploymentStatus: '',
  coProfessionCategory: '',
  coContractStartDate: '',
  coTrialPeriodCompleted: '',
  netMonthlySalary: '',
  coBorrowerNetSalary: '',
  familyAllowances: '',
  pensionsReceived: '',
  rentalIncomeGross: '',
  otherMonthlyIncome: '',
  rent: '',
  mortgage: '',
  consumerLoans: '',
  alimonyPaid: '',
  otherCharges: '',
  civility: '',
  firstName: '',
  lastName: '',
  birthName: '',
  birthDate: '',
  coCivility: '',
  coFirstName: '',
  coLastName: '',
  coBirthName: '',
  coBirthDate: '',
  email: '',
  phone: '',
  consentPartnerTransmission: false,
  consentAdvisorCallback: false,
};

/** Statuts sans contrat de travail : date de début non demandée. */
export const STATUSES_WITHOUT_CONTRACT: EmploymentStatus[] = ['UNEMPLOYED', 'STUDENT'];

/** Statuts salariés soumis à une période d'essai : la question n'est posée que pour eux. */
export const TRIAL_PERIOD_STATUSES: EmploymentStatus[] = ['CDI', 'CDD', 'CIVIL_SERVANT'];

export const hasTrialPeriod = (status: EmploymentStatus | ''): boolean => status !== '' && TRIAL_PERIOD_STATUSES.includes(status);

const trialToPayload = (status: EmploymentStatus | '', answer: 'yes' | 'no' | ''): boolean | null =>
  hasTrialPeriod(status) && answer !== '' ? answer === 'yes' : null;

/**
 * Nom de naissance demandé aux femmes mariées (même règle que ComparisonRequestDto::requiresBirthName côté API).
 * La situation familiale est celle du foyer : la règle vaut aussi pour la co-emprunteuse.
 */
export const needsBirthName = (civility: Civility | '', maritalStatus: MaritalStatus | ''): boolean => civility === 'MRS' && maritalStatus === 'MARRIED';

const birthNameToPayload = (civility: Civility | '', maritalStatus: MaritalStatus | '', birthName: string): string | null =>
  needsBirthName(civility, maritalStatus) ? birthName.trim() || null : null;

export const MIN_AMOUNT_CENTS = 30_000;
export const MAX_AMOUNT_CENTS = 10_000_000;
export const DURATION_OPTIONS = [6, 12, 18, 24, 36, 48, 60, 72, 84, 96] as const;

const cents = (value: string): number => parseEurosToCents(value) ?? 0;
const monthToDate = (value: string): string | null => (/^\d{4}-\d{2}$/.test(value) ? `${value}-01` : null);

/** Montant optionnel : vide accepté, sinon montant valide. */
const optionalMoneyError = (value: string): string | undefined =>
  value.trim() === '' || parseEurosToCents(value) !== null ? undefined : 'Montant invalide.';

const requiredMoneyError = (value: string, message: string): string | undefined => {
  const parsed = parseEurosToCents(value);

  return parsed === null || parsed <= 0 ? message : undefined;
};

export const toPayload = (state: TunnelFormState, acquisition: AcquisitionInput | null): ComparisonRequest => ({
  projectType: state.projectType as CreditProjectType,
  requestedAmountCents: cents(state.amount),
  durationMonths: Number(state.durationMonths),
  maritalStatus: state.maritalStatus as MaritalStatus,
  hasCoBorrower: state.hasCoBorrower,
  dependentChildrenCount: Number(state.dependentChildrenCount || '0'),
  housingStatus: state.housingStatus as HousingStatus,
  housingSince: monthToDate(state.housingSince),
  postalCode: state.postalCode.trim(),
  city: state.city.trim(),
  employmentStatus: state.employmentStatus as EmploymentStatus,
  professionCategory: state.professionCategory as ProfessionCategory,
  contractStartDate: monthToDate(state.contractStartDate),
  trialPeriodCompleted: trialToPayload(state.employmentStatus, state.trialPeriodCompleted),
  coBorrower: state.hasCoBorrower
    ? {
        employmentStatus: state.coEmploymentStatus as EmploymentStatus,
        professionCategory: state.coProfessionCategory as ProfessionCategory,
        contractStartDate: monthToDate(state.coContractStartDate),
        civility: state.coCivility === '' ? null : state.coCivility,
        firstName: state.coFirstName.trim() || null,
        lastName: state.coLastName.trim() || null,
        birthName: birthNameToPayload(state.coCivility, state.maritalStatus, state.coBirthName),
        birthDate: state.coBirthDate,
        trialPeriodCompleted: trialToPayload(state.coEmploymentStatus, state.coTrialPeriodCompleted),
      }
    : null,
  incomes: {
    netMonthlySalaryCents: cents(state.netMonthlySalary),
    coBorrowerNetSalaryCents: state.hasCoBorrower ? cents(state.coBorrowerNetSalary) : 0,
    familyAllowancesCents: cents(state.familyAllowances),
    pensionsReceivedCents: cents(state.pensionsReceived),
    rentalIncomeGrossCents: cents(state.rentalIncomeGross),
    otherMonthlyIncomeCents: cents(state.otherMonthlyIncome),
  },
  charges: {
    rentMonthlyCents: state.housingStatus === 'TENANT' ? cents(state.rent) : 0,
    mortgageMonthlyCents: state.housingStatus === 'OWNER_WITH_MORTGAGE' ? cents(state.mortgage) : 0,
    consumerLoansMonthlyCents: cents(state.consumerLoans),
    alimonyPaidMonthlyCents: cents(state.alimonyPaid),
    otherChargesMonthlyCents: cents(state.otherCharges),
  },
  civility: state.civility as Civility,
  firstName: state.firstName.trim(),
  lastName: state.lastName.trim(),
  birthName: birthNameToPayload(state.civility, state.maritalStatus, state.birthName),
  birthDate: state.birthDate,
  email: state.email.trim().toLowerCase(),
  phone: normalizeMobile(state.phone),
  consentPartnerTransmission: state.consentPartnerTransmission,
  consentAdvisorCallback: state.consentAdvisorCallback,
  // Mention affichée sous le bouton d'envoi : l'API ne l'exige que pour un nouveau client (section 7.3)
  consentClientSpaceCreation: true,
  acquisition,
});

const clean = (errors: FieldErrors): FieldErrors =>
  Object.fromEntries(Object.entries(errors).filter(([, message]) => message !== undefined)) as FieldErrors;

/**
 * Identifiants des étapes, dans l'ordre du tunnel. Les étapes « bis » (co-emprunteur) suivent
 * leur étape principale et ne s'affichent que pour un emprunt à deux.
 */
export const STEP_IDS = [
  'project',
  'family',
  'housing',
  'employment',
  'employmentCoBorrower',
  'incomes',
  'charges',
  'identity',
  'identityCoBorrower',
  'contact',
] as const;
export type StepId = (typeof STEP_IDS)[number];

/**
 * Règles d'une étape : visibilité, validation et chemins d'API qu'elle porte
 * (pour afficher au bon endroit une erreur 422 renvoyée par credit_api).
 */
export interface StepRules {
  validate: (state: TunnelFormState) => FieldErrors;
  /** Préfixes des chemins de violations de l'API (ex. « incomes. »). Le préfixe le plus long l'emporte. */
  apiPaths: string[];
  /** Étape bis : numérotée comme l'étape principale qu'elle complète (ex. « 4 bis »). */
  bisOf?: StepId;
  /** Étape affichée seulement si la condition est remplie (par défaut : toujours). */
  isVisible?: (state: TunnelFormState) => boolean;
}

const needsContract = (status: EmploymentStatus | ''): boolean => status !== '' && !STATUSES_WITHOUT_CONTRACT.includes(status);
const withCoBorrower = (state: TunnelFormState): boolean => state.hasCoBorrower;

export const STEP_RULES: Record<StepId, StepRules> = {
  project: {
    apiPaths: ['projectType', 'requestedAmountCents', 'durationMonths'],
    validate: (s) => {
      const amount = parseEurosToCents(s.amount);
      return clean({
        projectType: s.projectType === '' ? 'Choisissez votre projet.' : undefined,
        amount:
          amount === null || amount < MIN_AMOUNT_CENTS || amount > MAX_AMOUNT_CENTS ? 'Le montant doit être compris entre 300 € et 100 000 €.' : undefined,
        durationMonths: s.durationMonths === '' ? 'Choisissez une durée.' : undefined,
      });
    },
  },
  family: {
    apiPaths: ['maritalStatus', 'hasCoBorrower', 'dependentChildrenCount'],
    validate: (s) =>
      clean({
        maritalStatus: s.maritalStatus === '' ? 'Indiquez votre situation familiale.' : undefined,
        dependentChildrenCount: /^(?:\d|1[0-5])$/.test(s.dependentChildrenCount) ? undefined : 'Nombre d\'enfants invalide (0 à 15).',
      }),
  },
  housing: {
    apiPaths: ['housingStatus', 'housingSince', 'postalCode', 'city'],
    validate: (s) =>
      clean({
        housingStatus: s.housingStatus === '' ? 'Indiquez votre situation de logement.' : undefined,
        housingSince: s.housingSince !== '' && !isPastMonth(s.housingSince) ? 'Date d\'entrée invalide.' : undefined,
        postalCode: isMetropolitanPostalCode(s.postalCode)
          ? undefined
          : 'Notre service est réservé à la France métropolitaine et à la Corse (code postal de 01000 à 95999).',
        city: s.city.trim() === '' ? 'Indiquez votre ville.' : undefined,
      }),
  },
  employment: {
    apiPaths: ['employmentStatus', 'professionCategory', 'contractStartDate', 'trialPeriodCompleted'],
    validate: (s) =>
      clean({
        employmentStatus: s.employmentStatus === '' ? 'Indiquez votre situation professionnelle.' : undefined,
        professionCategory: s.professionCategory === '' ? 'Indiquez votre catégorie professionnelle.' : undefined,
        contractStartDate: needsContract(s.employmentStatus) && !isPastMonth(s.contractStartDate) ? 'Indiquez le mois de début de votre contrat ou activité.' : undefined,
        trialPeriodCompleted: hasTrialPeriod(s.employmentStatus) && s.trialPeriodCompleted === '' ? "Indiquez si votre période d'essai est terminée." : undefined,
      }),
  },
  employmentCoBorrower: {
    apiPaths: ['coBorrower.employmentStatus', 'coBorrower.professionCategory', 'coBorrower.contractStartDate', 'coBorrower.trialPeriodCompleted'],
    bisOf: 'employment',
    isVisible: withCoBorrower,
    validate: (s) =>
      clean({
        coEmploymentStatus: s.coEmploymentStatus === '' ? 'Indiquez la situation professionnelle du co-emprunteur.' : undefined,
        coProfessionCategory: s.coProfessionCategory === '' ? 'Indiquez la catégorie du co-emprunteur.' : undefined,
        coContractStartDate: needsContract(s.coEmploymentStatus) && !isPastMonth(s.coContractStartDate) ? 'Indiquez le mois de début de son contrat ou activité.' : undefined,
        coTrialPeriodCompleted: hasTrialPeriod(s.coEmploymentStatus) && s.coTrialPeriodCompleted === '' ? "Indiquez si sa période d'essai est terminée." : undefined,
      }),
  },
  incomes: {
    apiPaths: ['incomes.'],
    validate: (s) =>
      clean({
        netMonthlySalary:
          s.netMonthlySalary.trim() === '' ? 'Indiquez votre revenu net mensuel (0 si aucun).' : optionalMoneyError(s.netMonthlySalary),
        coBorrowerNetSalary: s.hasCoBorrower ? requiredMoneyError(s.coBorrowerNetSalary, 'Indiquez le salaire net mensuel du co-emprunteur.') : undefined,
        familyAllowances: optionalMoneyError(s.familyAllowances),
        pensionsReceived: optionalMoneyError(s.pensionsReceived),
        rentalIncomeGross: optionalMoneyError(s.rentalIncomeGross),
        otherMonthlyIncome: optionalMoneyError(s.otherMonthlyIncome),
      }),
  },
  charges: {
    apiPaths: ['charges.'],
    validate: (s) =>
      clean({
        rent: s.housingStatus === 'TENANT' ? requiredMoneyError(s.rent, 'Indiquez votre loyer mensuel.') : undefined,
        mortgage: s.housingStatus === 'OWNER_WITH_MORTGAGE' ? requiredMoneyError(s.mortgage, 'Indiquez votre mensualité de prêt immobilier.') : undefined,
        consumerLoans: optionalMoneyError(s.consumerLoans),
        alimonyPaid: optionalMoneyError(s.alimonyPaid),
        otherCharges: optionalMoneyError(s.otherCharges),
      }),
  },
  identity: {
    apiPaths: ['civility', 'firstName', 'lastName', 'birthName', 'birthDate'],
    validate: (s) =>
      clean({
        civility: s.civility === '' ? 'Indiquez votre civilité.' : undefined,
        firstName: s.firstName.trim() === '' ? 'Indiquez votre prénom.' : undefined,
        lastName: s.lastName.trim() === '' ? 'Indiquez votre nom.' : undefined,
        birthName: needsBirthName(s.civility, s.maritalStatus) && s.birthName.trim() === '' ? 'Indiquez votre nom de naissance.' : undefined,
        birthDate: isAllowedAge(s.birthDate) ? undefined : 'L\'emprunteur doit avoir entre 18 ans révolus et 80 ans.',
      }),
  },
  identityCoBorrower: {
    // « coBorrower » seul : erreur globale du bloc (ex. informations manquantes)
    apiPaths: ['coBorrower', 'coBorrower.civility', 'coBorrower.firstName', 'coBorrower.lastName', 'coBorrower.birthName', 'coBorrower.birthDate'],
    bisOf: 'identity',
    isVisible: withCoBorrower,
    validate: (s) =>
      clean({
        coCivility: s.coCivility === '' ? 'Indiquez la civilité du co-emprunteur.' : undefined,
        coFirstName: s.coFirstName.trim() === '' ? 'Indiquez le prénom du co-emprunteur.' : undefined,
        coLastName: s.coLastName.trim() === '' ? 'Indiquez le nom du co-emprunteur.' : undefined,
        coBirthName: needsBirthName(s.coCivility, s.maritalStatus) && s.coBirthName.trim() === '' ? 'Indiquez le nom de naissance de la co-emprunteuse.' : undefined,
        coBirthDate: isAllowedAge(s.coBirthDate) ? undefined : 'Le co-emprunteur doit avoir entre 18 ans révolus et 80 ans.',
      }),
  },
  contact: {
    apiPaths: ['email', 'phone', 'consentPartnerTransmission', 'consentClientSpaceCreation', 'acquisition'],
    validate: (s) =>
      clean({
        email: EMAIL.test(s.email.trim()) ? undefined : 'Saisissez une adresse e-mail valide.',
        phone: isFrenchMobile(s.phone) ? undefined : 'Saisissez un mobile français commençant par 06 ou 07.',
        consentPartnerTransmission: s.consentPartnerTransmission ? undefined : 'Ce consentement est nécessaire pour obtenir vos offres.',
      }),
  },
};

/** Étapes principales (sans les bis) : leur rang donne la numérotation affichée et le paramètre ?step=N. */
export const MAIN_STEP_IDS: StepId[] = STEP_IDS.filter((id) => STEP_RULES[id].bisOf === undefined);

/** Numéro affiché d'une étape : « 4 » pour une étape principale, « 4 bis » pour son étape bis. */
export const stepNumber = (id: StepId): string => {
  const parent = STEP_RULES[id].bisOf;

  return parent === undefined ? String(MAIN_STEP_IDS.indexOf(id) + 1) : `${MAIN_STEP_IDS.indexOf(parent) + 1} bis`;
};
/** Étapes affichées pour cette saisie (l'étape co-emprunteur n'apparaît que pour un emprunt à deux). */
export const visibleSteps = (state: TunnelFormState): StepId[] => STEP_IDS.filter((id) => STEP_RULES[id].isVisible?.(state) ?? true);

/** Champ du formulaire correspondant à un chemin de violation de l'API (affichage sous le champ). */
const API_FIELD_MAP: Record<string, TunnelField> = {
  requestedAmountCents: 'amount',
  'incomes.netMonthlySalaryCents': 'netMonthlySalary',
  'incomes.coBorrowerNetSalaryCents': 'coBorrowerNetSalary',
  'incomes.familyAllowancesCents': 'familyAllowances',
  'incomes.pensionsReceivedCents': 'pensionsReceived',
  'incomes.rentalIncomeGrossCents': 'rentalIncomeGross',
  'incomes.otherMonthlyIncomeCents': 'otherMonthlyIncome',
  'charges.rentMonthlyCents': 'rent',
  'charges.mortgageMonthlyCents': 'mortgage',
  'charges.consumerLoansMonthlyCents': 'consumerLoans',
  'charges.alimonyPaidMonthlyCents': 'alimonyPaid',
  'charges.otherChargesMonthlyCents': 'otherCharges',
  'coBorrower.civility': 'coCivility',
  'coBorrower.firstName': 'coFirstName',
  'coBorrower.lastName': 'coLastName',
  'coBorrower.birthName': 'coBirthName',
  'coBorrower.birthDate': 'coBirthDate',
  'coBorrower.employmentStatus': 'coEmploymentStatus',
  'coBorrower.professionCategory': 'coProfessionCategory',
  'coBorrower.contractStartDate': 'coContractStartDate',
  'coBorrower.trialPeriodCompleted': 'coTrialPeriodCompleted',
  coBorrower: 'coBirthDate',
};

/** Étape qui porte un chemin d'API : celle dont le préfixe correspondant est le plus long. */
const stepForPath = (path: string): StepId | null => {
  let bestId: StepId | null = null;
  let bestLength = -1;

  for (const id of STEP_IDS) {
    for (const prefix of STEP_RULES[id].apiPaths) {
      if ((path === prefix || path.startsWith(prefix)) && prefix.length > bestLength) {
        bestId = id;
        bestLength = prefix.length;
      }
    }
  }

  return bestId;
};

/**
 * Répartit les violations 422 de l'API : première étape concernée, erreurs par champ, messages sans champ.
 */
export const mapApiViolations = (violations: Record<string, string>): { step: StepId; errors: FieldErrors; unmapped: string[] } => {
  const errors: FieldErrors = {};
  const unmapped: string[] = [];
  let step: StepId = 'contact';

  Object.entries(violations).forEach(([path, message]) => {
    const stepId = stepForPath(path);
    if (stepId !== null && STEP_IDS.indexOf(stepId) < STEP_IDS.indexOf(step)) step = stepId;

    const field = API_FIELD_MAP[path] ?? (path in initialTunnelState ? (path as TunnelField) : undefined);
    if (field) {
      errors[field] = message;
    } else {
      unmapped.push(message);
    }
  });

  return { step, errors, unmapped };
};

