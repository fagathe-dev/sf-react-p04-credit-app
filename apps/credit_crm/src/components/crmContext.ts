import type { AdvisorProfile } from 'credit-front-core';
import { useOutletContext } from 'react-router';

export interface CrmContext {
  advisor: AdvisorProfile;
  /** À appeler quand une requête renvoie 401 : retour à la connexion. */
  handleUnauthorized: () => void;
}

/** Contexte fourni par CrmLayout aux pages protégées. */
export const useCrm = () => useOutletContext<CrmContext>();
