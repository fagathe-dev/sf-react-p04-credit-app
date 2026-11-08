import type { ReactNode } from 'react';

interface ChipProps {
  children: ReactNode;
  selected: boolean;
  onToggle: () => void;
  /** Rôle de couleur du design-system (classe chip-{rôle}) ; par défaut : primary. */
  colorRole?: 'primary' | 'secondary';
}

/**
 * Chip à bascule (chip du design-system) : bouton aria-pressed, .is-selected quand il est actif.
 */
export const Chip = ({ children, selected, onToggle, colorRole = 'primary' }: ChipProps) => (
  <button type="button" className={`chip chip-${colorRole}${selected ? ' is-selected' : ''}`} aria-pressed={selected} onClick={onToggle}>
    <svg className="chip-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
      {selected ? <path d="M5 12.5l4.5 4.5L19 7.5" /> : <path d="M12 5v14M5 12h14" />}
    </svg>
    {children}
  </button>
);
