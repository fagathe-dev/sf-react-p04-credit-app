import type { FC } from 'react';
import type { QuoteOfferDto } from '../../types/credit.types';
import { formatBpsToPercent, formatCentsToEuros } from '../../utils/moneyFormatter';

interface CardOfferProps {
  offer: QuoteOfferDto;
  /** Optionnel : sans lui (vue conseiller dans credit_crm, offre déjà choisie, offres expirées), le bouton n'est pas affiché. */
  onSelectOffer?: (offer: QuoteOfferDto) => void;
  /** Offre retenue par le client (dossier QUOTE_SUBSCRIBED ou suivant). */
  isSubscribed?: boolean;
}

/**
 * Carte d'offre unique pour tous les partenaires : style de la plateforme, sans branding partenaire.
 * Seuls le nom, le logo et les métriques financières changent.
 */
export const CardOffer: FC<CardOfferProps> = ({ offer, onSelectOffer, isSubscribed = false }) => {
  return (
    <article className={`card-offer${isSubscribed ? ' is-subscribed' : ''}`}>
      {isSubscribed && (
        <div className="offer-ribbon is-subscribed">
          <span>Offre choisie</span>
        </div>
      )}
      {!isSubscribed && offer.isBestRate && (
        <div className="offer-ribbon">
          <span>Meilleur taux pour votre profil</span>
        </div>
      )}

      <div className="offer-body">
        {/* Colonne 1 : logo et nom du partenaire */}
        <div className="partner-identity">
          <div className="logo-wrapper">
            <img src={offer.partnerLogo} alt={offer.partnerName} loading="lazy" />
          </div>
          <div className="partner-meta">
            <h3 className="partner-name">{offer.partnerName}</h3>
            <p className="product-name">{offer.productName}</p>
          </div>
        </div>

        {/* Colonne 2 : mensualité et TAEG */}
        <div className="financial-metrics">
          <div className="metric-primary">
            <span className="metric-label">Mensualité hors assurance</span>
            <strong className="installment-value">
              {formatCentsToEuros(offer.monthlyInstallmentCents)}
              <small>/mois</small>
            </strong>
            <span className="metric-label">
              {offer.durationMonths} mois · {formatCentsToEuros(offer.amountCents, { withCents: false })}
            </span>
          </div>

          <div className="metric-grid">
            <div className="metric-item">
              <span className="metric-label">TAEG fixe</span>
              <strong className="metric-value">{formatBpsToPercent(offer.aprBps)}</strong>
            </div>
            <div className="metric-item">
              <span className="metric-label">Taux débiteur</span>
              <span className="metric-value">{formatBpsToPercent(offer.nominalRateBps)}</span>
            </div>
            <div className="metric-item">
              <span className="metric-label">Coût total du crédit</span>
              <span className="metric-value">{formatCentsToEuros(offer.totalFinancingCostCents)}</span>
            </div>
            <div className="metric-item">
              <span className="metric-label">Dont intérêts</span>
              <span className="metric-value">{formatCentsToEuros(offer.totalInterestCents)}</span>
            </div>
          </div>
        </div>

        {/* Colonne 3 : appel à l'action */}
        <div className="offer-actions">
          {onSelectOffer && (
            <button type="button" className="btn-select-offer" onClick={() => onSelectOffer(offer)}>
              Choisir cette offre
            </button>
          )}
          <span className="insurance-hint">Assurance facultative : +{formatCentsToEuros(offer.monthlyInsuranceCents)}/mois</span>
          <span className="insurance-hint">Endettement après crédit : {formatBpsToPercent(offer.debtRatioAfterBps, 1)}</span>
        </div>
      </div>
    </article>
  );
};
