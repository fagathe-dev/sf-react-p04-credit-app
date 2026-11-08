import type { ClientProfile } from 'credit-front-core';
import { useOutletContext } from 'react-router';

export interface ClientSpaceContext {
  profile: ClientProfile;
  setProfile: (profile: ClientProfile) => void;
  /** À appeler quand une requête renvoie 401 : retour à la connexion. */
  handleUnauthorized: () => void;
}

/** Contexte fourni par ClientSpaceLayout aux pages protégées. */
export const useClientSpace = () => useOutletContext<ClientSpaceContext>();
