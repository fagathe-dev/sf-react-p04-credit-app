import { computeMonthlyInstallmentCents, computeTotalInterestCents } from 'credit-front-core/src/utils/loanMath';
import { formatCentsToEuros } from 'credit-front-core/src/utils/moneyFormatter';

/**
 * Estimateur de mensualité d'une page projet (formulaire [data-estimator]).
 *
 * Le taux débiteur et les bornes viennent du produit au plus petit TAEG du projet (attributs data-*,
 * rendus par Twig depuis le catalogue). Le lien « Comparer » reprend le montant et la durée choisis.
 */
interface EstimatorElements {
  amount: HTMLInputElement;
  duration: HTMLInputElement;
  amountOutput: HTMLElement;
  durationOutput: HTMLElement;
  monthlyOutput: HTMLElement;
  interestOutput: HTMLElement;
  cta: HTMLAnchorElement;
}

const query = <T extends Element>(form: HTMLElement, selector: string): T | null => form.querySelector<T>(selector);

const findElements = (form: HTMLElement): EstimatorElements | null => {
  const amount = query<HTMLInputElement>(form, 'input[name="amount"]');
  const duration = query<HTMLInputElement>(form, 'input[name="duration"]');
  const amountOutput = query<HTMLElement>(form, '[data-output="amount"]');
  const durationOutput = query<HTMLElement>(form, '[data-output="duration"]');
  const monthlyOutput = query<HTMLElement>(form, '[data-output="monthly"]');
  const interestOutput = query<HTMLElement>(form, '[data-output="interest"]');
  const cta = query<HTMLAnchorElement>(form, 'a[data-output="cta"]');

  if (!amount || !duration || !amountOutput || !durationOutput || !monthlyOutput || !interestOutput || !cta) {
    return null;
  }

  return { amount, duration, amountOutput, durationOutput, monthlyOutput, interestOutput, cta };
};

const initEstimator = (form: HTMLElement): void => {
  const elements = findElements(form);
  const nominalRateBps = Number(form.dataset.nominalRateBps);
  const tunnelUrl = form.dataset.tunnelUrl;

  if (!elements || !Number.isFinite(nominalRateBps) || !tunnelUrl) return;

  const render = (): void => {
    const amountCents = Number(elements.amount.value) * 100;
    const durationMonths = Number(elements.duration.value);

    elements.amountOutput.textContent = formatCentsToEuros(amountCents, { withCents: false });
    elements.durationOutput.textContent = `${durationMonths} mois`;
    elements.monthlyOutput.textContent = formatCentsToEuros(computeMonthlyInstallmentCents(amountCents, durationMonths, nominalRateBps));
    elements.interestOutput.textContent = formatCentsToEuros(computeTotalInterestCents(amountCents, durationMonths, nominalRateBps));

    const url = new URL(tunnelUrl);
    url.searchParams.set('amount', elements.amount.value);
    url.searchParams.set('duration', elements.duration.value);
    url.searchParams.set('step', '2');
    elements.cta.href = url.toString();
  };

  form.addEventListener('input', render);
  form.addEventListener('submit', (event) => event.preventDefault());
  form.classList.add('is-ready');
  render();
};

export const initMonthlyEstimators = (root: ParentNode): void => {
  root.querySelectorAll<HTMLElement>('[data-estimator]').forEach(initEstimator);
};
