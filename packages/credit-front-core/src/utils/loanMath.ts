/**
 * Mensualité constante indicative, en arithmétique entière (BigInt, virgule fixe à 18 décimales) :
 * M = C × t / (1 − (1 + t)^−n), t = taux débiteur annuel / 12. Même formule que
 * Credit\Core\Scoring\Calculator\LoanMathCalculator (le calcul qui fait foi reste celui de l'API).
 *
 * Utilisé par le simulateur rapide (credit_tunnel) et l'estimateur des pages projet (credit_front).
 */

const SCALE = 10n ** 18n;
const BPS_PER_YEAR_MONTH = 120_000n; // 10 000 points de base × 12 mois

const roundDiv = (numerator: bigint, denominator: bigint): bigint => (numerator + denominator / 2n) / denominator;

/** Mensualité hors assurance, en centimes. */
export const computeMonthlyInstallmentCents = (amountCents: number, durationMonths: number, nominalRateBps: number): number => {
  if (amountCents <= 0 || durationMonths <= 0) return 0;

  const amount = BigInt(Math.round(amountCents));
  const months = BigInt(Math.round(durationMonths));

  if (nominalRateBps <= 0) {
    return Number((amount + months - 1n) / months);
  }

  // t en virgule fixe
  const rate = roundDiv(BigInt(Math.round(nominalRateBps)) * SCALE, BPS_PER_YEAR_MONTH);
  let growth = SCALE;
  for (let i = 0n; i < months; i += 1n) {
    growth = (growth * (SCALE + rate)) / SCALE;
  }

  return Number(roundDiv(amount * rate * growth, (growth - SCALE) * SCALE));
};

/** Coût des intérêts sur toute la durée, en centimes. */
export const computeTotalInterestCents = (amountCents: number, durationMonths: number, nominalRateBps: number): number =>
  Math.max(0, computeMonthlyInstallmentCents(amountCents, durationMonths, nominalRateBps) * durationMonths - amountCents);

/** Part des revenus mensuels consacrée à la mensualité, en points de base. */
export const computeRatioBps = (partCents: number, totalCents: number): number => {
  if (totalCents <= 0) return 0;

  return Number(roundDiv(BigInt(Math.round(partCents)) * 10_000n, BigInt(Math.round(totalCents))));
};
