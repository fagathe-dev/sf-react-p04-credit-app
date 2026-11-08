import { useEffect } from 'react';

/**
 * Protection contre la perte d'une saisie en cours.
 *
 * - Sorties « navigateur » (fermeture de l'onglet, rechargement, saisie d'une URL) : évènement beforeunload.
 *   Le navigateur affiche alors SON message générique : le texte n'est pas personnalisable.
 * - Sorties prévues par l'application (logo) : la boîte de confirmation du tunnel (isLeaveGuarded),
 *   puis leaveWithoutPrompt() pour ne pas enchaîner une seconde confirmation du navigateur.
 */
let isActive = false;
let isBypassed = false;

const handleBeforeUnload = (event: BeforeUnloadEvent): void => {
  if (!isActive || isBypassed) return;
  event.preventDefault();
  // Navigateurs plus anciens : une valeur de retour non vide déclenche la confirmation
  event.returnValue = true;
};

/** Active la protection tant que `active` est vrai (saisie commencée et non envoyée). */
export const useLeaveGuard = (active: boolean): void => {
  useEffect(() => {
    if (!active) return undefined;

    isActive = true;
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      isActive = false;
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }, [active]);
};

/** Vrai si quitter la page ferait perdre une saisie en cours. */
export const isLeaveGuarded = (): boolean => isActive && !isBypassed;

/** Quitte la page sans confirmation (sortie déjà confirmée, ou fin de tunnel). */
export const leaveWithoutPrompt = (url: string): void => {
  isBypassed = true;
  window.location.assign(url);
};
