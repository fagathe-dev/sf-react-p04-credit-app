import { BrowserRouter, Navigate, Route, Routes } from 'react-router';
import { ClientSpaceLayout } from './components/ClientSpaceLayout';
import { ClientDashboardPage } from './pages/ClientDashboardPage';
import { ClientDossierPage } from './pages/ClientDossierPage';
import { ClientLoginPage } from './pages/ClientLoginPage';
import { ClientOtpPage } from './pages/ClientOtpPage';

/** Routes /connexion, /code, / et /dossiers/:id ; toutes sauf /connexion et /code passent par la garde ClientSpaceLayout. */
export const App = () => (
  <BrowserRouter>
    <Routes>
      <Route path="/connexion" element={<ClientLoginPage />} />
      <Route path="/code" element={<ClientOtpPage />} />
      <Route element={<ClientSpaceLayout />}>
        <Route index element={<ClientDashboardPage />} />
        <Route path="/dossiers/:id" element={<ClientDossierPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  </BrowserRouter>
);
