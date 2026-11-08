import {
  ArrowLeftIcon,
  DossierRecap,
  formatDate,
  getErrorMessage,
  getViolations,
  isNotFound,
  isUnauthorized,
  QuoteResultsView,
  WORKFLOW_STATUS_LABELS,
  type DossierDetail,
  type DossierWorkflowStatus,
  type QuoteOfferDto,
} from 'credit-front-core';
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { useClientSpace } from '../components/clientSpaceContext';
import { clientApi } from '../config';

/** Message d'avancement affiché au client, selon le statut (les autres statuts parlent d'eux-mêmes). */
const WORKFLOW_MESSAGES: Partial<Record<DossierWorkflowStatus, string>> = {
  QUOTE_SUBSCRIBED: 'Votre choix est enregistré. Un conseiller vérifie votre dossier avec l\'établissement partenaire et vous tient informé.',
  APPROVED: 'Bonne nouvelle : votre dossier est accepté. L\'établissement partenaire vous adresse votre offre de contrat.',
};

/** /dossiers/:id : « Résultat du {date} », récapitulatif, puis devis, pistes ou motifs. */
export const ClientDossierPage = () => {
  const { id = '' } = useParams();
  const { setProfile, handleUnauthorized } = useClientSpace();
  const [dossier, setDossier] = useState<DossierDetail | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [subscribeError, setSubscribeError] = useState<string | null>(null);
  const [isSubscribing, setIsSubscribing] = useState(false);
  const [isRequestingCallback, setIsRequestingCallback] = useState(false);

  useEffect(() => {
    let active = true;
    clientApi
      .dossier(id)
      .then((detail) => active && setDossier(detail))
      .catch((loadError: unknown) => {
        if (!active) return;
        if (isUnauthorized(loadError)) handleUnauthorized();
        else setError(isNotFound(loadError) ? 'Ce dossier est introuvable.' : getErrorMessage(loadError));
      });

    return () => {
      active = false;
    };
  }, [id, handleUnauthorized]);

  const requestCallback = async () => {
    setIsRequestingCallback(true);
    try {
      setProfile(await clientApi.updateConsents(true));
      setDossier(await clientApi.dossier(id));
    } catch (callbackError) {
      if (isUnauthorized(callbackError)) handleUnauthorized();
      else setError(getErrorMessage(callbackError));
    } finally {
      setIsRequestingCallback(false);
    }
  };

  const subscribe = async (offer: QuoteOfferDto) => {
    if (isSubscribing) return;
    if (!window.confirm(`Choisir l'offre ${offer.productName} de ${offer.partnerName} ? Ce choix est définitif pour ce dossier.`)) return;

    setIsSubscribing(true);
    setSubscribeError(null);
    try {
      setDossier(await clientApi.subscribe(id, offer.offerCode));
    } catch (subscribeFailure) {
      if (isUnauthorized(subscribeFailure)) handleUnauthorized();
      else setSubscribeError(Object.values(getViolations(subscribeFailure))[0] ?? getErrorMessage(subscribeFailure));
    } finally {
      setIsSubscribing(false);
    }
  };

  if (error) {
    return (
      <div className="dossier-page">
        <Link className="back-link" to="/">
          <ArrowLeftIcon /> Mes dossiers
        </Link>
        <p className="alert alert-danger">{error}</p>
      </div>
    );
  }

  if (!dossier) {
    return <p className="page-loading">Chargement du dossier…</p>;
  }

  return (
    <div className="dossier-page">
      <Link className="back-link" to="/">
        <ArrowLeftIcon /> Mes dossiers
      </Link>
      <div className="dossier-heading">
        <h1>Résultat du {formatDate(dossier.submittedAt)}</h1>
        <span className={`badge badge-${dossier.workflowStatus.toLowerCase()}`}>{WORKFLOW_STATUS_LABELS[dossier.workflowStatus]}</span>
      </div>

      {WORKFLOW_MESSAGES[dossier.workflowStatus] ? (
        <div className="alert alert-info" role="status">
          <p>{WORKFLOW_MESSAGES[dossier.workflowStatus]}</p>
        </div>
      ) : null}
      {subscribeError ? (
        <p className="alert alert-danger" role="alert">
          {subscribeError}
        </p>
      ) : null}

      <QuoteResultsView
        result={dossier}
        onSelectOffer={dossier.workflowStatus === 'QUOTES_GENERATED' ? subscribe : undefined}
        subscribedOfferCode={dossier.subscribedOfferCode}
        quotesExpireAt={dossier.decision === 'INSTANT_OFFERS' && !dossier.subscribedOfferCode ? dossier.quotesExpireAt : null}
        quotesExpired={dossier.quotesExpired}
        callbackConsentGiven={dossier.consentAdvisorCallbackAt !== null}
        phone={dossier.phone}
        onRequestCallback={requestCallback}
        isRequestingCallback={isRequestingCallback}
      />

      <section className="recap">
        <h2>Récapitulatif de votre demande</h2>
        <DossierRecap request={dossier.request} />
      </section>
    </div>
  );
};
