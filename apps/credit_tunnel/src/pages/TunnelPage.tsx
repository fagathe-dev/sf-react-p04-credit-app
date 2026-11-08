import { ComparisonTunnel } from '../components/tunnel/ComparisonTunnel';

/** Tunnel à 8 étapes, puis redirection vers credit_client (section 7.2). */
export const TunnelPage = () => (
  <div className="container narrow">
    <ComparisonTunnel />
  </div>
);
