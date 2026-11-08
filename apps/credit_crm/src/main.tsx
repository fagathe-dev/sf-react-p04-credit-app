import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import 'credit-front-core/src/styles/index.scss';
import './styles/main.scss';
import { App } from './App';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
