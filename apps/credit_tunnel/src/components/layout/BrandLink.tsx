import { useState, type MouseEvent } from 'react';
import { APP_NAME, FRONT_URL } from '../../config';
import { isLeaveGuarded, leaveWithoutPrompt } from '../../utils/leaveGuard';
import { ConfirmDialog } from '../ui/ConfirmDialog';

const BrandMark = () => (
  <svg viewBox="0 0 32 32" aria-hidden="true">
    <rect width="32" height="32" rx="8" fill="#1d4ed8" />
    <path d="M8 21l5-6 4 3 6-8" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
  </svg>
);

/** Logo : retour au site vitrine (VITE_FRONT_URL), avec confirmation si une demande est en cours de saisie. */
export const BrandLink = () => {
  const [isConfirming, setIsConfirming] = useState(false);

  const handleClick = (event: MouseEvent<HTMLAnchorElement>) => {
    // Ouverture dans un nouvel onglet (Ctrl, Cmd, Maj, clic molette) : la saisie n'est pas perdue
    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    if (!isLeaveGuarded()) return;

    event.preventDefault();
    setIsConfirming(true);
  };

  return (
    <>
      <a className="brand" href={FRONT_URL} onClick={handleClick}>
        <BrandMark />
        <span>{APP_NAME}</span>
      </a>
      <ConfirmDialog
        open={isConfirming}
        title="Êtes-vous sûr de vouloir quitter le comparateur ?"
        confirmLabel="Quitter"
        cancelLabel="Continuer ma demande"
        onConfirm={() => leaveWithoutPrompt(FRONT_URL)}
        onCancel={() => setIsConfirming(false)}
      >
        <p>Votre demande n'a pas encore été envoyée. Vos réponses restent enregistrées dans cet onglet jusqu'à sa fermeture.</p>
      </ConfirmDialog>
    </>
  );
};
