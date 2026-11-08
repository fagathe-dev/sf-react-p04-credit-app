/**
 * Centimes <-> euros, points de base <-> pourcentages.
 * Aucune division flottante : les montants restent des entiers, seules les chaînes affichées ont des décimales.
 */

const NBSP = ' ';
const integerFormatter = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });

const splitCents = (cents: number): { negative: boolean; euros: number; rest: number } => {
  const safe = Math.round(cents);
  const abs = Math.abs(safe);
  const rest = abs % 100;

  return { negative: safe < 0, euros: (abs - rest) / 100, rest };
};

/** 1500000 → « 15 000,00 € » ; { withCents: false } → « 15 000 € ». */
export const formatCentsToEuros = (cents: number, options: { withCents?: boolean } = {}): string => {
  const { withCents = true } = options;
  const { negative, euros, rest } = splitCents(cents);
  const decimals = withCents ? `,${String(rest).padStart(2, '0')}` : '';

  return `${negative ? '-' : ''}${integerFormatter.format(euros)}${decimals}${NBSP}€`;
};

/** 380 → « 3,80 % » ; decimals = 1 → « 3,8 % ». */
export const formatBpsToPercent = (bps: number, decimals: 0 | 1 | 2 = 2): string => {
  const safe = Math.round(bps);
  const abs = Math.abs(safe);
  const divisor = 10 ** (2 - decimals);
  // Arrondi au plus proche sur des entiers
  const scaled = Math.floor((abs + Math.floor(divisor / 2)) / divisor);
  const unit = 10 ** decimals;
  const integerPart = Math.floor(scaled / unit);
  const fraction = decimals > 0 ? `,${String(scaled % unit).padStart(decimals, '0')}` : '';

  return `${safe < 0 ? '-' : ''}${integerPart}${fraction}${NBSP}%`;
};

/**
 * Saisie utilisateur en euros (« 15 000 », « 1250,50 », « 1 250.5 ») → centimes entiers.
 * Retourne null si la saisie n'est pas un montant valide.
 */
export const parseEurosToCents = (input: string): number | null => {
  const normalized = input.replace(/[\s  €]/g, '').replace(',', '.');

  if (normalized === '') return null;

  const match = /^(\d{1,9})(?:\.(\d{0,2}))?$/.exec(normalized);
  if (!match) return null;

  const euros = Number(match[1]);
  const cents = Number((match[2] ?? '').padEnd(2, '0'));

  return euros * 100 + cents;
};

/** 1500000 → « 15000 » (valeur d'un champ de saisie en euros, sans séparateur). */
export const centsToEurosInput = (cents: number): string => {
  const { negative, euros, rest } = splitCents(cents);

  return `${negative ? '-' : ''}${euros}${rest > 0 ? `,${String(rest).padStart(2, '0')}` : ''}`;
};

/** Montant en euros entiers (paramètres d'URL du tunnel) → centimes. */
export const eurosToCents = (euros: number): number => Math.round(euros) * 100;
