import type { RejectionDto } from '../../types/credit.types';
import { AlertIcon } from '../icons/ProjectIconsSvg';

interface NotFinanceableCardProps {
  rejection: RejectionDto;
}

/**
 * Dossier non financable : motifs clairs, sans promesse de rappel ni de solution.
 */
export const NotFinanceableCard = ({ rejection }: NotFinanceableCardProps) => (
  <section className="rejection-card">
    <header className="rejection-head">
      <span className="rejection-icon">
        <AlertIcon />
      </span>
      <div>
        <h2>Nous ne pouvons pas vous proposer d'offre pour cette demande</h2>
        <p className="muted">Aucun montant ni aucune durée ne permet à nos partenaires de financer ce projet dans votre situation actuelle.</p>
      </div>
    </header>
    <ul className="rejection-reasons">
      {rejection.reasons.map((reason) => (
        <li key={reason}>{reason}</li>
      ))}
    </ul>
    <p className="muted small">Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager.</p>
  </section>
);
