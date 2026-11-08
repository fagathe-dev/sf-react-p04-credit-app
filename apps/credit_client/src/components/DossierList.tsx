import { formatCentsToEuros, formatDate, PROJECT_LABELS, ProjectIcon, ArrowRightIcon, WORKFLOW_STATUS_LABELS, type DossierSummary } from 'credit-front-core';
import { Link } from 'react-router';
import { TUNNEL_URL } from '../config';

/** Dossiers du client, du plus récent au plus ancien (ordre fourni par l'API). */
export const DossierList = ({ dossiers }: { dossiers: DossierSummary[] }) => {
  if (dossiers.length === 0) {
    return (
      <section className="card">
        <h2>Mes dossiers</h2>
        <p className="muted">Vous n'avez pas encore de dossier.</p>
        <a className="btn btn-primary" href={TUNNEL_URL}>
          Comparer les offres
        </a>
      </section>
    );
  }

  return (
    <section className="dossier-section">
      <h2>Mes dossiers</h2>
      <ul className="dossier-list">
        {dossiers.map((dossier) => (
          <li key={dossier.id}>
            <Link className="dossier-item" to={`/dossiers/${dossier.id}`}>
              <span className="dossier-icon">
                <ProjectIcon project={dossier.projectType} />
              </span>
              <span className="dossier-main">
                <strong>
                  {PROJECT_LABELS[dossier.projectType]} · {formatCentsToEuros(dossier.requestedAmountCents, { withCents: false })} sur {dossier.durationMonths} mois
                </strong>
                <span className="muted small">Résultat du {formatDate(dossier.submittedAt)}</span>
              </span>
              <span className={`badge badge-${dossier.workflowStatus.toLowerCase()}`}>
                {dossier.workflowStatus === 'QUOTES_GENERATED'
                  ? `${dossier.quotesCount} offre${dossier.quotesCount > 1 ? 's' : ''} · jusqu'au ${formatDate(dossier.quotesExpireAt)}`
                  : WORKFLOW_STATUS_LABELS[dossier.workflowStatus]}
              </span>
              <ArrowRightIcon />
            </Link>
          </li>
        ))}
      </ul>
    </section>
  );
};
