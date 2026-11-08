import { getErrorMessage, isUnauthorized, LogoutIcon, type AdvisorProfile } from 'credit-front-core';
import { useCallback, useEffect, useState } from 'react';
import { Link, Outlet, useNavigate } from 'react-router';
import { APP_NAME, crmApi } from '../config';
import type { CrmContext } from './crmContext';

/**
 * Gabarit du back-office et garde de route : une réponse 401 redirige vers /connexion.
 */
export const CrmLayout = () => {
  const navigate = useNavigate();
  const [advisor, setAdvisor] = useState<AdvisorProfile | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleUnauthorized = useCallback(() => navigate('/connexion', { replace: true }), [navigate]);

  useEffect(() => {
    let active = true;
    crmApi
      .me()
      .then((me) => active && setAdvisor(me))
      .catch((meError: unknown) => {
        if (!active) return;
        if (isUnauthorized(meError)) handleUnauthorized();
        else setError(getErrorMessage(meError));
      });

    return () => {
      active = false;
    };
  }, [handleUnauthorized]);

  const logout = async () => {
    try {
      await crmApi.logout();
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
              <rect width="32" height="32" rx="8" fill="#111827" />
              <path d="M8 21l5-6 4 3 6-8" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            <span>{APP_NAME} · Conseillers</span>
          </Link>
          {advisor ? (
            <>
              <span className="muted small">
                {advisor.firstName} {advisor.lastName}
              </span>
              <button type="button" className="btn btn-ghost" onClick={logout}>
                <LogoutIcon /> Déconnexion
              </button>
            </>
          ) : null}
        </div>
      </header>
      <main className="app-main">
        <div className="container">
          {error ? <p className="alert alert-danger">{error}</p> : null}
          {advisor ? <Outlet context={{ advisor, handleUnauthorized } satisfies CrmContext} /> : !error && <p className="page-loading">Chargement…</p>}
        </div>
      </main>
    </>
  );
};
