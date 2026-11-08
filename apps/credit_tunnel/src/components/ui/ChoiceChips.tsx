import { useId, useRef, type KeyboardEvent } from 'react';

export interface ChoiceOption<T extends string> {
  value: T;
  label: string;
}

interface ChoiceChipsProps<T extends string> {
  label: string;
  value: T | '';
  options: Array<ChoiceOption<T>>;
  onChange: (value: T) => void;
  error?: string;
  hint?: string;
}

/**
 * Choix unique présenté en chips (chip du design-system, ex. Oui / Non, civilité) :
 * role="radiogroup", chips role="radio" + aria-checked (.is-selected), tabulation itinérante,
 * flèches pour changer de choix, Début / Fin pour le premier / dernier.
 */
export const ChoiceChips = <T extends string>({ label, value, options, onChange, error, hint }: ChoiceChipsProps<T>) => {
  const id = useId();
  const refs = useRef<Array<HTMLButtonElement | null>>([]);
  const selectedIndex = options.findIndex((option) => option.value === value);
  // Tabulation itinérante : seul le choix coché (ou le premier) est atteignable avec Tab
  const tabbableIndex = selectedIndex >= 0 ? selectedIndex : 0;
  const describedBy = [error ? `${id}-error` : null, hint ? `${id}-hint` : null].filter(Boolean).join(' ') || undefined;

  const select = (index: number) => {
    const option = options[index];
    if (!option) return;
    onChange(option.value);
    refs.current[index]?.focus();
  };

  const handleKeyDown = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
    const last = options.length - 1;
    const target = {
      ArrowRight: index === last ? 0 : index + 1,
      ArrowDown: index === last ? 0 : index + 1,
      ArrowLeft: index === 0 ? last : index - 1,
      ArrowUp: index === 0 ? last : index - 1,
      Home: 0,
      End: last,
    }[event.key];

    if (target !== undefined) {
      event.preventDefault();
      select(target);
    }
  };

  return (
    <div className="field">
      <span className="field-label" id={`${id}-label`}>
        {label}
      </span>
      <div
        className={`chip-group${error ? ' is-invalid' : ''}`}
        role="radiogroup"
        aria-labelledby={`${id}-label`}
        aria-describedby={describedBy}
        aria-invalid={error ? true : undefined}
      >
        {options.map((option, index) => {
          const checked = option.value === value;

          return (
            <button
              key={option.value}
              ref={(element) => {
                refs.current[index] = element;
              }}
              type="button"
              role="radio"
              aria-checked={checked}
              className={`chip chip-primary chip-choice${checked ? ' is-selected' : ''}`}
              tabIndex={index === tabbableIndex ? 0 : -1}
              onClick={() => onChange(option.value)}
              onKeyDown={(event) => handleKeyDown(event, index)}
            >
              {checked ? (
                <svg className="chip-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                  <path d="M5 12.5l4.5 4.5L19 7.5" />
                </svg>
              ) : null}
              {option.label}
            </button>
          );
        })}
      </div>
      {hint ? (
        <p className="field-hint" id={`${id}-hint`}>
          {hint}
        </p>
      ) : null}
      {error ? (
        <p className="field-error" id={`${id}-error`}>
          {error}
        </p>
      ) : null}
    </div>
  );
};
