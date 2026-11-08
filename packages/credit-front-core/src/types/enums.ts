/**
 * Miroir TS des enums PHP de credit-core (packages/credit-core/src/Enum).
 * Objets `as const` + type union : pas d'`enum` TypeScript (erasableSyntaxOnly).
 */

type ValueOf<T> = T[keyof T];

export const CreditProjectType = {
  GREEN_LOAN: 'green_loan',
  AUTO: 'auto',
  HOME_IMPROVEMENT: 'home_improvement',
  PERSONAL_GOODS: 'personal_goods',
  CASH_TREASURY: 'cash_treasury',
} as const;
export type CreditProjectType = ValueOf<typeof CreditProjectType>;

export const PROJECT_LABELS: Record<CreditProjectType, string> = {
  green_loan: 'Crédit vert',
  auto: 'Prêt auto',
  home_improvement: 'Prêt travaux',
  personal_goods: 'Prêt personnel',
  cash_treasury: 'Trésorerie express',
};

export const EmploymentStatus = {
  CDI: 'CDI',
  CIVIL_SERVANT: 'CIVIL_SERVANT',
  CDD: 'CDD',
  INDEPENDENT: 'INDEPENDENT',
  RETIRED: 'RETIRED',
  STUDENT: 'STUDENT',
  UNEMPLOYED: 'UNEMPLOYED',
} as const;
export type EmploymentStatus = ValueOf<typeof EmploymentStatus>;

export const EMPLOYMENT_STATUS_LABELS: Record<EmploymentStatus, string> = {
  CDI: 'CDI',
  CIVIL_SERVANT: 'Fonctionnaire',
  CDD: 'CDD / intérim',
  INDEPENDENT: 'Indépendant',
  RETIRED: 'Retraité',
  STUDENT: 'Étudiant',
  UNEMPLOYED: 'Sans emploi',
};

export const ProfessionCategory = {
  EXECUTIVE: 'EXECUTIVE',
  EMPLOYEE: 'EMPLOYEE',
  WORKER: 'WORKER',
  INTERMEDIATE: 'INTERMEDIATE',
  SELF_EMPLOYED: 'SELF_EMPLOYED',
  INACTIVE: 'INACTIVE',
} as const;
export type ProfessionCategory = ValueOf<typeof ProfessionCategory>;

export const PROFESSION_CATEGORY_LABELS: Record<ProfessionCategory, string> = {
  EXECUTIVE: 'Cadre',
  EMPLOYEE: 'Employé',
  WORKER: 'Ouvrier',
  INTERMEDIATE: 'Profession intermédiaire',
  SELF_EMPLOYED: 'Artisan, commerçant, profession libérale',
  INACTIVE: 'Sans activité professionnelle',
};

export const HousingStatus = {
  TENANT: 'TENANT',
  OWNER_WITH_MORTGAGE: 'OWNER_WITH_MORTGAGE',
  OWNER_WITHOUT_MORTGAGE: 'OWNER_WITHOUT_MORTGAGE',
  HOSTED_FREE: 'HOSTED_FREE',
  COMPANY_HOUSING: 'COMPANY_HOUSING',
} as const;
export type HousingStatus = ValueOf<typeof HousingStatus>;

export const HOUSING_STATUS_LABELS: Record<HousingStatus, string> = {
  TENANT: 'Locataire',
  OWNER_WITH_MORTGAGE: 'Propriétaire avec crédit immobilier',
  OWNER_WITHOUT_MORTGAGE: 'Propriétaire sans crédit immobilier',
  HOSTED_FREE: 'Hébergé gratuitement',
  COMPANY_HOUSING: 'Logement de fonction',
};

export const MaritalStatus = {
  SINGLE: 'SINGLE',
  MARRIED: 'MARRIED',
  PACS: 'PACS',
  DIVORCED: 'DIVORCED',
  WIDOWED: 'WIDOWED',
} as const;
export type MaritalStatus = ValueOf<typeof MaritalStatus>;

export const MARITAL_STATUS_LABELS: Record<MaritalStatus, string> = {
  SINGLE: 'Célibataire',
  MARRIED: 'Marié(e)',
  PACS: 'Pacsé(e)',
  DIVORCED: 'Divorcé(e)',
  WIDOWED: 'Veuf / veuve',
};

export const Civility = { MR: 'MR', MRS: 'MRS', OTHER: 'OTHER' } as const;
export type Civility = ValueOf<typeof Civility>;

/** Libellés des choix de civilité (formulaires). */
export const CIVILITY_LABELS: Record<Civility, string> = { MR: 'M.', MRS: 'Mme', OTHER: 'Autre' };

/** Formule de politesse devant un nom : aucune pour « Autre ». */
export const CIVILITY_TITLES: Record<Civility, string> = { MR: 'M.', MRS: 'Mme', OTHER: '' };

/** « Mme Camille Martin », « Camille Martin » pour une civilité « Autre ». */
export const formatPersonName = (civility: Civility | null | undefined, firstName: string | null | undefined, lastName: string | null | undefined): string =>
  [civility ? CIVILITY_TITLES[civility] : '', firstName ?? '', lastName ?? ''].filter((part) => part !== '').join(' ');

export const RiskGrade = {
  GRADE_A: 'GRADE_A',
  GRADE_B: 'GRADE_B',
  GRADE_C: 'GRADE_C',
  GRADE_D: 'GRADE_D',
  GRADE_E: 'GRADE_E',
} as const;
export type RiskGrade = ValueOf<typeof RiskGrade>;

export const ScoringDecision = {
  INSTANT_OFFERS: 'INSTANT_OFFERS',
  ADVISOR_CALLBACK_NEEDED: 'ADVISOR_CALLBACK_NEEDED',
  HARD_REJECT: 'HARD_REJECT',
} as const;
export type ScoringDecision = ValueOf<typeof ScoringDecision>;

export const DECISION_LABELS: Record<ScoringDecision, string> = {
  INSTANT_OFFERS: 'Offres immédiates',
  ADVISOR_CALLBACK_NEEDED: 'Dossier à ajuster',
  HARD_REJECT: 'Dossier non financable',
};

export const RuleSeverity = { STRUCTURAL: 'STRUCTURAL', ADJUSTABLE: 'ADJUSTABLE' } as const;
export type RuleSeverity = ValueOf<typeof RuleSeverity>;

export const ClientStatus = {
  CREATED: 'CREATED',
  OTP_REQUIRED: 'OTP_REQUIRED',
  AUTHENTICATED: 'AUTHENTICATED',
} as const;
export type ClientStatus = ValueOf<typeof ClientStatus>;

export const DossierStatus = { PENDING_VERIFICATION: 'PENDING_VERIFICATION', ACTIVE: 'ACTIVE' } as const;
export type DossierStatus = ValueOf<typeof DossierStatus>;

export const CallbackStatus = {
  TO_CALL: 'TO_CALL',
  NO_ANSWER: 'NO_ANSWER',
  CALLBACK_SCHEDULED: 'CALLBACK_SCHEDULED',
  ADJUSTED: 'ADJUSTED',
  DECLINED: 'DECLINED',
  CLOSED: 'CLOSED',
} as const;
export type CallbackStatus = ValueOf<typeof CallbackStatus>;

export const CALLBACK_STATUS_LABELS: Record<CallbackStatus, string> = {
  TO_CALL: 'À appeler',
  NO_ANSWER: 'Pas de réponse',
  CALLBACK_SCHEDULED: 'Rappel planifié',
  ADJUSTED: 'Dossier ajusté',
  DECLINED: 'Refus du client',
  CLOSED: 'Clos',
};

export const AdjustmentKind = {
  LONGER_DURATION: 'LONGER_DURATION',
  LOWER_AMOUNT: 'LOWER_AMOUNT',
  COMPROMISE: 'COMPROMISE',
} as const;
export type AdjustmentKind = ValueOf<typeof AdjustmentKind>;

export const ADJUSTMENT_KIND_LABELS: Record<AdjustmentKind, string> = {
  LONGER_DURATION: 'Durée plus longue',
  LOWER_AMOUNT: 'Montant plus bas',
  COMPROMISE: 'Compromis montant et durée',
};

/** Avancement commercial d'un dossier (App\Enum\DossierWorkflowStatusEnum). */
export const DossierWorkflowStatus = {
  QUOTES_GENERATED: 'QUOTES_GENERATED',
  NO_OFFER_ADVISOR_QUEUE: 'NO_OFFER_ADVISOR_QUEUE',
  QUOTE_SUBSCRIBED: 'QUOTE_SUBSCRIBED',
  APPROVED: 'APPROVED',
  REJECTED: 'REJECTED',
} as const;
export type DossierWorkflowStatus = ValueOf<typeof DossierWorkflowStatus>;

export const WORKFLOW_STATUS_LABELS: Record<DossierWorkflowStatus, string> = {
  QUOTES_GENERATED: 'Offres en attente de choix',
  NO_OFFER_ADVISOR_QUEUE: 'À ajuster avec un conseiller',
  QUOTE_SUBSCRIBED: 'Offre choisie, en cours de validation',
  APPROVED: 'Dossier accepté',
  REJECTED: 'Dossier refusé',
};

/** Priorité d'appel dans la file des conseillers (RG-01 à RG-04). */
export const CallbackPriority = {
  P1: 'P1',
  P1_BIS: 'P1_BIS',
  P2: 'P2',
  P3: 'P3',
  NONE: 'NONE',
} as const;
export type CallbackPriority = ValueOf<typeof CallbackPriority>;

export const CALLBACK_PRIORITY_LABELS: Record<CallbackPriority, string> = {
  P1: 'P1 · Dossier chaud ajustable',
  P1_BIS: 'P1 bis · Gros montant hésitant',
  P2: 'P2 · Relance devis standard',
  P3: 'P3 · Dernière relance avant expiration',
  NONE: 'Sans priorité',
};

export const CALLBACK_PRIORITY_SHORT_LABELS: Record<CallbackPriority, string> = {
  P1: 'P1',
  P1_BIS: 'P1 bis',
  P2: 'P2',
  P3: 'P3',
  NONE: '—',
};

export const EmailNotificationStatus = { SENT: 'SENT', FAILED: 'FAILED' } as const;
export type EmailNotificationStatus = ValueOf<typeof EmailNotificationStatus>;

/** Liste ordonnée des valeurs d'un objet `as const` (options de formulaire). */
export const enumValues = <T extends Record<string, string>>(values: T): Array<T[keyof T]> =>
  Object.values(values) as Array<T[keyof T]>;
