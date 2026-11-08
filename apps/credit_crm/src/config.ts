import { createCrmApi, createHttpClient } from 'credit-front-core';

export const APP_NAME = import.meta.env.VITE_APP_NAME || 'Comparateur de crédit';

export const crmApi = createCrmApi(createHttpClient(import.meta.env.VITE_API_URL || 'http://localhost:8000'));
