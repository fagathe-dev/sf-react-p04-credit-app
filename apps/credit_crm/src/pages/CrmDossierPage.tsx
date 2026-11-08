import {
  CALLBACK_PRIORITY_LABELS,
  CALLBACK_PRIORITY_SHORT_LABELS,
  WORKFLOW_STATUS_LABELS,
  ArrowLeftIcon,
  DossierRecap,
  formatDateTime,
  formatPhone,
  getErrorMessage,
  isNotFound,
  isUnauthorized,
  QuoteResultsView,
  type CrmDossierDetail,
} from 'credit-front-core';
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { CallbackFollowUpForm } from '../components/CallbackFollowUpForm';
import { useCrm } from '../components/crmContext';
import { crmApi } from '../config';

const ACQUISITION_LABELS: Record<string, string> = {
  utm_source: 'Source',
  utm_medium: 'Support',
  utm_campaign: 'Campagne',
  referrer: 'Page d\'origine',
};

/** /dossiers/:id : coordonnées, récapitulatif, score, devis ou pistes, suivi d'appel. */
export const CrmDossierPage = () => {
  const { id = '' } = useParams();
  const { handleUnauthorized } = useCrm();
  const [detail, setDetail] = useState<CrmDossierDetail | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    crmApi
      .dossier(id)
      .then((response) => active && setDetail(response))
      .catch((loadError: unknown) => {
        if (!active) return;
        if (isUnauthorized(loadError)) handleUnauthorized();
        else setError(isNotFound(loadError) ? 'Dossier introuvable ou sans consentement de rappel.' : getErrorMessage(loadError));
      });

    return () => {
      active = false;
    };
  }, [id, handleUnauthorized]);

  if (error) {
    return (
      <div className="crm-dossier">
        <Link className="back-link" to="/">
          <ArrowLeftIcon /> File de rappels
        </Link>
        <p className="alert alert-danger">{error}</p>
      </div>
    );
  }

  if (!detail) {
    return <p className="page-loading">Chargement du dossier…</p>;
  }

  const { dossier } = detail;
  const acquisition = Object.entries(detail.acquisition ?? {}).filter(([, value]) => value);

  return (
    <div className="crm-dossier">
      <Link className="back-link" to="/">
        <ArrowLeftIcon /> File de rappels
      </Link>

      <div className="crm-grid">
        <div className="crm-main">
          <div className="dossier-heading">
            <h1>{detail.clientName}</h1>
            <span className={`badge badge-${dossier.workflowStatus.toLowerCase()}`}>{WORKFLOW_STATUS_LABELS[dossier.workflowStatus]}</span>
          </div>
          <p className="muted">
            Résultat du {formatDateTime(dossier.submittedAt)}
            {dossier.decision === 'INSTANT_OFFERS' ? ` · offres valables jusqu'au ${formatDateTime(dossier.quotesExpireAt)}` : ''}
          </p>

          <section className={`priority-card priority-${detail.priority.toLowerCase()}`} aria-label="Priorité d'appel">
            <span className={`priority-badge priority-${detail.priority.toLowerCase()}`}>{CALLBACK_PRIORITY_SHORT_LABELS[detail.priority]}</span>
            <div>
              <strong>{CALLBACK_PRIORITY_LABELS[detail.priority]}</strong>
              <p>{detail.priorityReason}</p>
            </div>
          </section>

          <QuoteResultsView
            result={dossier}
            subscribedOfferCode={dossier.subscribedOfferCode}
            callbackConsentGiven={dossier.consentAdvisorCallbackAt !== null}
            phone={detail.phone}
            showScoreBreakdown
          />

          <section>
            <h2>Récapitulatif de la demande</h2>
            <DossierRecap request={dossier.request} hideIdentity />
          </section>
        </div>

        <aside className="crm-side">
          <section className="card">
            <h2>Coordonnées</h2>
            <dl className="facts-list">
              {dossier.request.birthName ? (
                <div>
                  <dt>Nom de naissance</dt>
                  <dd>{dossier.request.birthName}</dd>
                </div>
              ) : null}
              <div>
                <dt>Mobile</dt>
                <dd>
                  <a href={`tel:${detail.phone}`}>{formatPhone(detail.phone)}</a>
                </dd>
              </div>
              <div>
                <dt>E-mail</dt>
                <dd>
                  <a href={`mailto:${detail.email}`}>{detail.email}</a>
                </dd>
              </div>
              <div>
                <dt>Rappel accepté le</dt>
                <dd>{formatDateTime(dossier.consentAdvisorCallbackAt)}</dd>
              </div>
            </dl>
          </section>

          <CallbackFollowUpForm key={detail.callbackUpdatedAt ?? 'new'} detail={detail} onSaved={setDetail} />

          <section className="card">
            <h2>E-mails envoyés</h2>
            {detail.emails.length === 0 ? (
              <p className="muted small">Aucun e-mail pour ce dossier.</p>
            ) : (
              <ol className="email-history">
                {detail.emails.map((email) => (
                  <li key={`${email.type}-${email.sentAt}`} className={email.status === 'FAILED' ? 'is-failed' : undefined}>
                    <strong>{email.label}</strong>
                    <span className="small">{email.subject}</span>
                    <span className="muted small">
                      {formatDateTime(email.sentAt)}
                      {email.status === 'FAILED' ? ' · échec, nouvel essai au prochain passage' : ''}
                    </span>
                  </li>
                ))}
              </ol>
            )}
          </section>

          {acquisition.length > 0 ? (
            <section className="card">
              <h2>Acquisition</h2>
              <dl className="facts-list">
                {acquisition.map(([key, value]) => (
                  <div key={key}>
                    <dt>{ACQUISITION_LABELS[key] ?? key}</dt>
                    <dd>{value}</dd>
                  </div>
                ))}
              </dl>
            </section>
          ) : null}
        </aside>
      </div>
    </div>
  );
};
