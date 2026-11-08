import { BrowserRouter, Navigate, Route, Routes } from 'react-router';
import { CrmLayout } from './components/CrmLayout';
import { CallbackQueuePage } from './pages/CallbackQueuePage';
import { CrmDossierPage } from './pages/CrmDossierPage';
import { CrmLoginPage } from './pages/CrmLoginPage';
import { CrmOtpPage } from './pages/CrmOtpPage';

/** Routes /connexion, /code, / et /dossiers/:id ; toutes sauf /connexion et /code passent par la garde CrmLayout. */
export const App = () => (
  <BrowserRouter>
    <Routes>
      <Route path="/connexion" element={<CrmLoginPage />} />
      <Route path="/code" element={<CrmOtpPage />} />
      <Route element={<CrmLayout />}>
        <Route index element={<CallbackQueuePage />} />
        <Route path="/dossiers/:id" element={<CrmDossierPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  </BrowserRouter>
);
