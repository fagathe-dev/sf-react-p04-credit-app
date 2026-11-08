import { useEffect, useId, useRef, type ReactNode } from 'react';

interface ConfirmDialogProps {
  open: boolean;
  title: string;
  children: ReactNode;
  confirmLabel: string;
  cancelLabel: string;
  onConfirm: () => void;
  onCancel: () => void;
}

/**
 * Boîte de confirmation modale sur l'élément natif <dialog> : piège du focus, Échap et fond inerte
 * sont gérés par le navigateur. Le focus initial va sur l'action d'annulation (choix sans risque).
 * Le <dialog> n'a pas de marge intérieure : un clic qui le cible directement est un clic sur le fond.
 */
export const ConfirmDialog = ({ open, title, children, confirmLabel, cancelLabel, onConfirm, onCancel }: ConfirmDialogProps) => {
  const id = useId();
  const dialogRef = useRef<HTMLDialogElement>(null);

  useEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return;

    if (open && !dialog.open) dialog.showModal();
    if (!open && dialog.open) dialog.close();
  }, [open]);

  return (
    <dialog
      ref={dialogRef}
      className="confirm-dialog"
      aria-labelledby={`${id}-title`}
      aria-describedby={`${id}-body`}
      // Échap ferme le <dialog> : on resynchronise l'état du parent
      onClose={onCancel}
      // Clic sur le fond (hors du contenu) : même effet qu'« annuler »
      onClick={(event) => {
        if (event.target === event.currentTarget) onCancel();
      }}
    >
      <div className="confirm-dialog-content">
        <h2 id={`${id}-title`}>{title}</h2>
        <div id={`${id}-body`} className="confirm-dialog-body">
          {children}
        </div>
        <div className="confirm-dialog-actions">
          {/* Premier élément focalisable : showModal() lui donne le focus */}
          <button type="button" className="btn btn-outline" onClick={onCancel}>
            {cancelLabel}
          </button>
          <button type="button" className="btn btn-primary" onClick={onConfirm}>
            {confirmLabel}
          </button>
        </div>
      </div>
    </dialog>
  );
};
