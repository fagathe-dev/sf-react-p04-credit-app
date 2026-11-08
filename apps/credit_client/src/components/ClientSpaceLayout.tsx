import { getErrorMessage, isUnauthorized, LogoutIcon, type ClientProfile } from 'credit-front-core';
import { useCallback, useEffect, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router';
import { clientApi, TUNNEL_URL } from '../config';
import type { ClientSpaceContext } from './clientSpaceContext';

/**
 * Gabarit de l'espace client et garde de route : une réponse 401 redirige vers /connexion.
 */
export const ClientSpaceLayout = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [profile, setProfile] = useState<ClientProfile | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleUnauthorized = useCallback(() => {
    navigate('/connexion', { replace: true, state: { from: location.pathname } });
  }, [navigate, location.pathname]);

  useEffect(() => {
    let active = true;
    clientApi
      .me()
      .then((me) => active && setProfile(me))
      .catch((meError: unknown) => {
        if (!active) return;
        if (isUnauthorized(meError)) {
          handleUnauthorized();
        } else {
          setError(getErrorMessage(meError));
        }
      });

    return () => {
      active = false;
    };
  }, [handleUnauthorized]);

  const logout = async () => {
    try {
      await clientApi.logout();
    } finally {
      navigate('/connexion', { replace: true });
    }
  };

  return (
    <>
      <header className="app-header">
        <div className="container app-header-inner">
          <Link className="brand" to="/">
            <svg viewBox="0 0 32 32" aria-hidden="true">
              <rect width="32" height="32" rx="8" fill="#1d4ed8" />
              <path d="M8 21l5-6 4 3 6-8" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            <span>Mon espace</span>
          </Link>
          <a className="btn btn-primary" href={TUNNEL_URL}>
            Nouvelle demande
          </a>
          {profile ? (
            <button type="button" className="btn btn-ghost" onClick={logout}>
              <LogoutIcon /> Déconnexion
            </button>
          ) : null}
        </div>
      </header>
      <main className="app-main">
        <div className="container">
          {error ? (
            <div className="alert alert-danger" role="alert">
              <p>{error}</p>
            </div>
          ) : null}
          {profile ? <Outlet context={{ profile, setProfile, handleUnauthorized } satisfies ClientSpaceContext} /> : !error && <p className="page-loading">Chargement de votre espace…</p>}
        </div>
      </main>
    </>
  );
};
