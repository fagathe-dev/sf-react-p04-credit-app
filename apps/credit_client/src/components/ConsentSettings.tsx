import { CheckIcon, formatDate, getErrorMessage, isUnauthorized } from 'credit-front-core';
import { useId, useState } from 'react';
import { clientApi } from '../config';
import { useClientSpace } from './clientSpaceContext';

/**
 * État des consentements (datés) et bascule du rappel par un conseiller.
 */
export const ConsentSettings = () => {
  const { profile, setProfile, handleUnauthorized } = useClientSpace();
  const switchId = useId();
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const { consents } = profile;

  const toggleCallback = async (consent: boolean) => {
    setIsSaving(true);
    setError(null);
    try {
      setProfile(await clientApi.updateConsents(consent));
    } catch (updateError) {
      if (isUnauthorized(updateError)) {
        handleUnauthorized();
        return;
      }
      setError(getErrorMessage(updateError));
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <section className="card consent-card">
      <h2>Mes consentements</h2>
      <ul className="consent-list">
        <li>
          <CheckIcon />
          <div>
            <strong>Création de l'espace client</strong>
            <span className="muted small">Accepté le {formatDate(consents.clientSpaceCreatedAt)}</span>
          </div>
        </li>
        {consents.partnerTransmissionAt ? (
          <li>
            <CheckIcon />
            <div>
              <strong>Transmission aux partenaires</strong>
              <span className="muted small">Dernière demande le {formatDate(consents.partnerTransmissionAt)}</span>
            </div>
          </li>
        ) : null}
      </ul>

      <div className="consent-switch">
        <label className="toggle" htmlFor={switchId}>
          <input id={switchId} type="checkbox" role="switch" checked={consents.advisorCallback} disabled={isSaving} onChange={(event) => toggleCallback(event.target.checked)} />
          <span className="toggle-track" aria-hidden="true" />
          <span>
            <strong>Rappel par un conseiller</strong>
            <span className="muted small">
              {consents.advisorCallback
                ? `Accepté le ${formatDate(consents.advisorCallbackAt)} : un conseiller peut vous rappeler pour ajuster vos dossiers ou vous proposer d'autres solutions.`
                : 'Activez-le pour qu\'un conseiller vous rappelle et étudie avec vous un montant plus bas ou une durée plus longue.'}
            </span>
          </span>
        </label>
      </div>
      {error ? (
        <p className="field-error" role="alert">
          {error}
        </p>
      ) : null}
    </section>
  );
};
