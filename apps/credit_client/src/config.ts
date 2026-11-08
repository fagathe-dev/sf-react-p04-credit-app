import { createClientApi, createHttpClient } from 'credit-front-core';

export const APP_NAME = import.meta.env.VITE_APP_NAME || 'Comparateur de crédit';
/** Lien « Nouvelle demande » vers credit_tunnel. */
export const TUNNEL_URL = `${(import.meta.env.VITE_TUNNEL_URL || 'http://localhost:5172').replace(/\/+$/, '')}/comparer`;

export const clientApi = createClientApi(createHttpClient(import.meta.env.VITE_API_URL || 'http://localhost:8000'));
