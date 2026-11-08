import { BrowserRouter, Navigate, Route, Routes } from 'react-router';
import { BrandLink } from './components/layout/BrandLink';
import { LegalBanner } from './components/layout/LegalBanner';
import { TunnelPage } from './pages/TunnelPage';

/**
 * Tunnel de comparaison (/comparer). Le simulateur rapide est sur le site vitrine (credit_front, /simulateur),
 * qui ouvre ce tunnel à l'étape 2 avec projet, montant, durée et revenu. Le logo ramène au site vitrine.
 */
export const App = () => (
  <BrowserRouter>
    <header className="app-header">
      <div className="container app-header-inner">
        <BrandLink />
      </div>
    </header>
    <main className="app-main">
      <Routes>
        <Route path="/comparer" element={<TunnelPage />} />
        <Route path="*" element={<Navigate to="/comparer" replace />} />
      </Routes>
    </main>
    <LegalBanner />
  </BrowserRouter>
);
