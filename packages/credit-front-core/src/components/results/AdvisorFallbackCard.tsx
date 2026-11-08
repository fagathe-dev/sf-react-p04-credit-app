import type { AdvisorFallbackDto } from '../../types/credit.types';
import { ADJUSTMENT_KIND_LABELS } from '../../types/enums';
import { formatPhone } from '../../utils/dateFormatter';
import { formatBpsToPercent, formatCentsToEuros } from '../../utils/moneyFormatter';
import { InfoIcon, PhoneIcon } from '../icons/ProjectIconsSvg';

interface AdvisorFallbackCardProps {
  fallback: AdvisorFallbackDto;
  /** Consentement de rappel en vigueur (il a pu être donné depuis l'espace client après le résultat). */
  callbackConsentGiven: boolean;
  phone?: string | null;
  /** Bouton « Être rappelé » (espace client) : absent dans la vue conseiller. */
  onRequestCallback?: () => void;
  isRequestingCallback?: boolean;
}

/**
 * Dossier à ajuster : pourquoi la demande ne passe pas telle quelle, pistes montant / durée,
 * et rappel par un conseiller selon le consentement.
 */
export const AdvisorFallbackCard = ({ fallback, callbackConsentGiven, phone, onRequestCallback, isRequestingCallback = false }: AdvisorFallbackCardProps) => {
  const displayedPhone = formatPhone(phone ?? fallback.phone);

  return (
    <section className="fallback-card">
      <header className="fallback-head">
        <span className="fallback-icon">
          <PhoneIcon />
        </span>
        <div>
          <h2>Votre projet est finançable avec quelques ajustements</h2>
          {callbackConsentGiven ? (
            <p className="lead">
              Un conseiller spécialisé va vous rappeler au <strong>{displayedPhone}</strong> pour étudier avec vous un montant plus bas ou une durée plus longue.
            </p>
          ) : (
            <p className="lead">Aucune offre ne correspond exactement à votre demande, mais des solutions existent.</p>
          )}
        </div>
      </header>

      {fallback.reasons.length > 0 ? (
        <div className="fallback-reasons">
          <h3>Pourquoi votre demande ne passe pas telle quelle</h3>
          <ul>
            {fallback.reasons.map((reason) => (
              <li key={reason}>{reason}</li>
            ))}
          </ul>
        </div>
      ) : null}

      {fallback.suggestions.length > 0 ? (
        <div className="suggestions">
          <h3>Pistes envisageables</h3>
          <ul className="suggestion-list">
            {fallback.suggestions.map((suggestion) => (
              <li key={`${suggestion.kind}-${suggestion.offerCode}`} className="suggestion">
                <span className="suggestion-kind">{ADJUSTMENT_KIND_LABELS[suggestion.kind]}</span>
                <strong>{suggestion.label}</strong>
                <span className="suggestion-partner">
                  <img src={suggestion.partnerLogo} alt="" loading="lazy" />
                  {suggestion.partnerName} · {suggestion.productName}
                </span>
                <dl>
                  <div>
                    <dt>Mensualité</dt>
                    <dd>{formatCentsToEuros(suggestion.monthlyInstallmentCents)}</dd>
                  </div>
                  <div>
                    <dt>TAEG fixe</dt>
                    <dd>{formatBpsToPercent(suggestion.aprBps)}</dd>
                  </div>
                  <div>
                    <dt>Endettement</dt>
                    <dd>{formatBpsToPercent(suggestion.debtRatioAfterBps, 1)}</dd>
                  </div>
                </dl>
              </li>
            ))}
          </ul>
          <p className="muted small">
            <InfoIcon width={16} height={16} /> Pistes indicatives : c'est le conseiller qui valide avec vous ce que vous acceptez de modifier.
          </p>
        </div>
      ) : null}

      {!callbackConsentGiven && onRequestCallback ? (
        <div className="fallback-callback">
          <p>Vous souhaitez en parler avec un conseiller ? Il vous rappelle au {displayedPhone} pour ajuster votre dossier.</p>
          <button type="button" className="btn btn-primary" onClick={onRequestCallback} disabled={isRequestingCallback}>
            {isRequestingCallback ? 'Enregistrement…' : 'Être rappelé par un conseiller'}
          </button>
        </div>
      ) : null}
    </section>
  );
};
