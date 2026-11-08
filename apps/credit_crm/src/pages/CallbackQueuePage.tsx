import { getErrorMessage, isUnauthorized, type CrmCallbackItem, type Paginated } from 'credit-front-core';
import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';
import { CallbackTable, type CallbackTableFilters } from '../components/CallbackTable';
import { useCrm } from '../components/crmContext';
import { crmApi } from '../config';

const readFilters = (params: URLSearchParams): CallbackTableFilters => ({
  priority: (params.get('priority') ?? '') as CallbackTableFilters['priority'],
  decision: (params.get('decision') ?? '') as CallbackTableFilters['decision'],
  callbackStatus: (params.get('callbackStatus') ?? '') as CallbackTableFilters['callbackStatus'],
  workflowStatus: (params.get('workflowStatus') ?? '') as CallbackTableFilters['workflowStatus'],
  page: Math.max(1, Number(params.get('page') ?? '1') || 1),
});

/** / : file de rappels priorisée, filtres conservés dans l'URL. */
export const CallbackQueuePage = () => {
  const { handleUnauthorized } = useCrm();
  const [params, setParams] = useSearchParams();
  const filters = readFilters(params);
  const { priority, decision, callbackStatus, workflowStatus, page } = filters;
  // Clé de la requête en cours : le chargement se déduit de la clé de la dernière réponse reçue
  const queryKey = `${priority}|${decision}|${callbackStatus}|${workflowStatus}|${page}`;
  const [result, setResult] = useState<{ key: string; data: Paginated<CrmCallbackItem> | null; error: string | null } | null>(null);
  const isLoading = result?.key !== queryKey;
  const data = result?.data ?? null;
  const error = result?.key === queryKey ? result.error : null;

  useEffect(() => {
    let active = true;
    crmApi
      .callbacks({ priority, decision, callbackStatus, workflowStatus, page, limit: 20 })
      .then((response) => {
        if (active) setResult({ key: queryKey, data: response, error: null });
      })
      .catch((loadError: unknown) => {
        if (!active) return;
        if (isUnauthorized(loadError)) handleUnauthorized();
        else setResult((previous) => ({ key: queryKey, data: previous?.data ?? null, error: getErrorMessage(loadError) }));
      });

    return () => {
      active = false;
    };
  }, [priority, decision, callbackStatus, workflowStatus, page, queryKey, handleUnauthorized]);

  const changeFilters = (next: CallbackTableFilters) => {
    const search = new URLSearchParams();
    if (next.priority) search.set('priority', next.priority);
    if (next.workflowStatus) search.set('workflowStatus', next.workflowStatus);
    if (next.decision) search.set('decision', next.decision);
    if (next.callbackStatus) search.set('callbackStatus', next.callbackStatus);
    if (next.page > 1) search.set('page', String(next.page));
    setParams(search);
  };

  return (
    <div className="queue-page">
      <h1>File de rappels</h1>
      <p className="muted">
        Dossiers dont le client a accepté d'être rappelé, classés par priorité : P1 dossier chaud ajustable, P1 bis gros montant hésitant, P2 relance
        devis standard, P3 dernière relance avant expiration.
      </p>
      {error ? <p className="alert alert-danger">{error}</p> : null}
      <CallbackTable data={data} filters={filters} onFiltersChange={changeFilters} isLoading={isLoading} />
    </div>
  );
};
