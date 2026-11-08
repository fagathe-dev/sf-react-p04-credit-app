import { computeMonthlyInstallmentCents, computeRatioBps } from 'credit-front-core/src/utils/loanMath';
import { formatBpsToPercent, formatCentsToEuros } from 'credit-front-core/src/utils/moneyFormatter';

/**
 * Simulateur rapide (formulaire [data-quick-simulator], templates/simulator/index.html.twig).
 *
 * Calcule en direct la mensualité indicative avec l'offre au plus petit TAEG dont les bornes acceptent
 * le montant et la durée (barème JSON rendu par Twig). Le formulaire reste un GET natif vers credit_tunnel :
 * le bouton « Comparer les offres personnalisées » fonctionne aussi sans JavaScript.
 */
interface SimulatorOffer {
  projectType: string;
  offerCode: string;
  productName: string;
  partnerName: string;
  nominalRateBps: number;
  aprBps: number;
  minAmountCents: number;
  maxAmountCents: number;
  minDurationMonths: number;
  maxDurationMonths: number;
}

/** Seuil d'alerte indicatif : plafond d'endettement le plus haut du catalogue. */
const DEBT_RATIO_WARNING_BPS = 3_500;

const eurosToCents = (value: string): number | null => (/^\d{1,7}$/.test(value.trim()) ? Number(value.trim()) * 100 : null);

const findOffer = (offers: SimulatorOffer[], project: string, amountCents: number, duration: number): SimulatorOffer | null =>
  offers
    .filter(
      (offer) =>
        offer.projectType === project &&
        amountCents >= offer.minAmountCents &&
        amountCents <= offer.maxAmountCents &&
        duration >= offer.minDurationMonths &&
        duration <= offer.maxDurationMonths,
    )
    .sort((a, b) => a.aprBps - b.aprBps)[0] ?? null;

const initSimulator = (form: HTMLFormElement): void => {
  const json = form.querySelector<HTMLScriptElement>('[data-simulator-offers]')?.textContent;
  const estimate = form.querySelector<HTMLElement>('[data-simulator-estimate]');
  const empty = form.querySelector<HTMLElement>('[data-simulator-empty]');
  const ratioRow = form.querySelector<HTMLElement>('[data-ratio-row]');
  const ratioWarning = form.querySelector<HTMLElement>('[data-ratio-warning]');
  const output = (name: string) => form.querySelector<HTMLElement>(`[data-output="${name}"]`);

  if (!json || !estimate || !empty || !ratioRow || !ratioWarning) return;

  const offers = JSON.parse(json) as SimulatorOffer[];

  const render = (): void => {
    const data = new FormData(form);
    const project = String(data.get('project') ?? '');
    const amountCents = eurosToCents(String(data.get('amount') ?? ''));
    const duration = Number(data.get('duration'));
    const incomeCents = eurosToCents(String(data.get('income') ?? ''));
    const offer = project !== '' && amountCents !== null ? findOffer(offers, project, amountCents, duration) : null;

    if (offer === null || amountCents === null) {
      estimate.hidden = true;
      empty.hidden = false;
      empty.textContent =
        project === ''
          ? 'Choisissez un projet pour voir une estimation.'
          : 'Aucun produit ne couvre ce montant et cette durée : ajustez-les, ou comparez quand même les offres.';
      return;
    }

    const monthlyCents = computeMonthlyInstallmentCents(amountCents, duration, offer.nominalRateBps);
    const ratioBps = incomeCents !== null && incomeCents > 0 ? computeRatioBps(monthlyCents, incomeCents) : null;

    const set = (name: string, text: string) => {
      const element = output(name);
      if (element) element.textContent = text;
    };
    set('monthly', formatCentsToEuros(monthlyCents));
    set('apr', formatBpsToPercent(offer.aprBps));
    set('product', `${offer.productName} (${offer.partnerName})`);
    set('ratio', ratioBps === null ? '' : formatBpsToPercent(ratioBps, 1));

    ratioRow.hidden = ratioBps === null;
    ratioWarning.hidden = ratioBps === null || ratioBps <= DEBT_RATIO_WARNING_BPS;
    estimate.hidden = false;
    empty.hidden = true;
  };

  form.addEventListener('input', render);
  form.addEventListener('change', render);
  render();
};

export const initQuickSimulators = (root: ParentNode): void => {
  root.querySelectorAll<HTMLFormElement>('form[data-quick-simulator]').forEach(initSimulator);
};
