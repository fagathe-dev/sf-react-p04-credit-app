import type { ReactNode } from 'react';
import type { DossierResult, QuoteOfferDto } from '../../types/credit.types';
import type { ScoringDecision } from '../../types/enums';
import { formatDate } from '../../utils/dateFormatter';
import { AdvisorFallbackCard } from './AdvisorFallbackCard';
import { CardOffer } from './CardOffer';
import { NotFinanceableCard } from './NotFinanceableCard';
import { ScoringBadgePanel } from './ScoringBadgePanel';

interface QuoteResultsViewProps {
  result: DossierResult;
  /** Bouton des cartes d'offres (credit_client, offres non expirées et non choisies) ; absent dans la vue conseiller. */
  onSelectOffer?: (offer: QuoteOfferDto) => void;
  /** Offre retenue par le client, mise en avant. */
  subscribedOfferCode?: string | null;
  quotesExpireAt?: string | null;
  quotesExpired?: boolean;
  callbackConsentGiven?: boolean;
  phone?: string | null;
  onRequestCallback?: () => void;
  isRequestingCallback?: boolean;
  showScoreBreakdown?: boolean;
}

/**
 * Résultat d'un dossier : résumé de scoring puis, selon la décision, offres, rattrapage ou refus.
 * Monté par ClientDossierPage (credit_client) et CrmDossierPage (credit_crm), jamais dans le tunnel.
 */
export const QuoteResultsView = ({
  result,
  onSelectOffer,
  subscribedOfferCode = null,
  quotesExpireAt = null,
  quotesExpired = false,
  callbackConsentGiven = false,
  phone,
  onRequestCallback,
  isRequestingCallback,
  showScoreBreakdown = false,
}: QuoteResultsViewProps) => {
  const validity = quotesExpireAt
    ? quotesExpired
      ? `Ces offres ont expiré le ${formatDate(quotesExpireAt)} : refaites une demande pour obtenir des offres à jour.`
      : `Offres valables jusqu'au ${formatDate(quotesExpireAt)}.`
    : null;

  // Un rendu par décision : ajouter une décision = ajouter une entrée
  const renderers: Record<ScoringDecision, () => ReactNode> = {
    INSTANT_OFFERS: () => (
      <section className="offers">
        <h2>
          {result.quotes.length} offre{result.quotes.length > 1 ? 's' : ''} pour votre profil
        </h2>
        <p className="muted">
          Offres triées par TAEG croissant, calculées pour le montant et la durée demandés.{validity ? ` ${validity}` : ''}
        </p>
        <div className="offer-list">
          {result.quotes.map((offer) => (
            <CardOffer
              key={offer.offerCode}
              offer={offer}
              isSubscribed={offer.offerCode === subscribedOfferCode}
              onSelectOffer={subscribedOfferCode || quotesExpired ? undefined : onSelectOffer}
            />
          ))}
        </div>
      </section>
    ),
    ADVISOR_CALLBACK_NEEDED: () =>
      result.advisorFallback ? (
        <AdvisorFallbackCard
          fallback={result.advisorFallback}
          callbackConsentGiven={callbackConsentGiven}
          phone={phone}
          onRequestCallback={onRequestCallback}
          isRequestingCallback={isRequestingCallback}
        />
      ) : null,
    HARD_REJECT: () => (result.rejection ? <NotFinanceableCard rejection={result.rejection} /> : null),
  };

  return (
    <div className="quote-results">
      <ScoringBadgePanel summary={result.scoringSummary} showBreakdown={showScoreBreakdown} />
      {renderers[result.decision]()}
    </div>
  );
};
