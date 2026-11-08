import { LEGAL_LINKS } from '../../config';

/**
 * Bandeau des pages légales du site vitrine, en bas du tunnel.
 * Nouvel onglet (target="_blank") : la saisie en cours n'est pas quittée, donc pas de confirmation de sortie.
 */
export const LegalBanner = () => (
  <footer className="legal-banner">
    <nav className="container" aria-label="Informations légales">
      <ul>
        {LEGAL_LINKS.map(({ label, href }) => (
          <li key={href}>
            <a href={href} target="_blank" rel="noopener noreferrer">
              {label}
              <span className="visually-hidden"> (nouvel onglet)</span>
              <svg className="external-icon" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
                <path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
              </svg>
            </a>
          </li>
        ))}
      </ul>
    </nav>
  </footer>
);
