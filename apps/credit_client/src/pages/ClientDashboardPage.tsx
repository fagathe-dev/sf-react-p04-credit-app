import { getErrorMessage, isUnauthorized, type DossierSummary } from 'credit-front-core';
import { useEffect, useState } from 'react';
import { ConsentSettings } from '../components/ConsentSettings';
import { DossierList } from '../components/DossierList';
import { ProfileCard } from '../components/ProfileCard';
import { useClientSpace } from '../components/clientSpaceContext';
import { clientApi } from '../config';

/** / : profil, consentements et dossiers. */
export const ClientDashboardPage = () => {
  const { profile, handleUnauthorized } = useClientSpace();
  const [dossiers, setDossiers] = useState<DossierSummary[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    clientApi
      .dossiers()
      .then((list) => active && setDossiers(list))
      .catch((listError: unknown) => {
        if (!active) return;
        if (isUnauthorized(listError)) handleUnauthorized();
        else setError(getErrorMessage(listError));
      });

    return () => {
      active = false;
    };
  }, [handleUnauthorized]);

  return (
    <div className="dashboard">
      <h1>Bonjour {profile.firstName}</h1>
      <div className="dashboard-grid">
        <div className="dashboard-main">
          {error ? <p className="alert alert-danger">{error}</p> : null}
          {dossiers ? <DossierList dossiers={dossiers} /> : !error && <p className="page-loading">Chargement des dossiers…</p>}
        </div>
        <aside className="dashboard-side">
          <ProfileCard profile={profile} />
          <ConsentSettings />
        </aside>
      </div>
    </div>
  );
};
