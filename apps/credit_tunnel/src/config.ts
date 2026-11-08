import { createCreditApi, createHttpClient } from 'credit-front-core';

export const APP_NAME = import.meta.env.VITE_APP_NAME || 'Comparateur de crédit';
/** Site vitrine (credit_front) : cible du logo et des pages légales. */
export const FRONT_URL = (import.meta.env.VITE_FRONT_URL || 'http://localhost:8001').replace(/\/+$/, '');

/** Pages légales du site vitrine (routes de LegalController), ouvertes dans un nouvel onglet depuis le tunnel. */
export const LEGAL_LINKS = [
  { label: 'Mentions légales', href: `${FRONT_URL}/mentions-legales` },
  { label: 'Données personnelles', href: `${FRONT_URL}/gestion-donnees-personnelles` },
  { label: 'Fonctionnement du comparateur', href: `${FRONT_URL}/mentions-comparateurs` },
  { label: 'CGU', href: `${FRONT_URL}/cgu` },
] as const;
export const CLIENT_URL = (import.meta.env.VITE_CLIENT_URL || 'http://localhost:5171').replace(/\/+$/, '');

export const creditApi = createCreditApi(createHttpClient(import.meta.env.VITE_API_URL || 'http://localhost:8000'));
