import { useId, type ReactNode } from 'react';

/**
 * Champs du tunnel. Chaque champ affiche son libellé, son aide et son erreur, reliés par aria-describedby.
 */
interface BaseFieldProps {
  label: string;
  error?: string;
  hint?: ReactNode;
}

const describedBy = (id: string, error?: string, hint?: ReactNode): string | undefined =>
  [error ? `${id}-error` : null, hint ? `${id}-hint` : null].filter(Boolean).join(' ') || undefined;

const FieldMessages = ({ id, error, hint }: { id: string; error?: string; hint?: ReactNode }) => (
  <>
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
  </>
);

interface TextFieldProps extends BaseFieldProps {
  value: string;
  onChange: (value: string) => void;
  type?: 'text' | 'email' | 'tel' | 'date' | 'month';
  inputMode?: 'text' | 'numeric' | 'email' | 'tel' | 'decimal';
  autoComplete?: string;
  maxLength?: number;
  placeholder?: string;
  max?: string;
}

export const TextField = ({ label, error, hint, value, onChange, type = 'text', ...inputProps }: TextFieldProps) => {
  const id = useId();

  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      <input
        id={id}
        type={type}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy(id, error, hint)}
        {...inputProps}
      />
      <FieldMessages id={id} error={error} hint={hint} />
    </div>
  );
};

/** Montant en euros (saisie libre « 1 250,50 »), converti en centimes à l'envoi. */
export const MoneyField = ({ label, error, hint, value, onChange }: BaseFieldProps & { value: string; onChange: (value: string) => void }) => {
  const id = useId();

  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      <div className="money-input">
        <input
          id={id}
          type="text"
          inputMode="decimal"
          autoComplete="off"
          value={value}
          onChange={(event) => onChange(event.target.value)}
          aria-invalid={error ? true : undefined}
          aria-describedby={describedBy(id, error, hint)}
        />
        <span aria-hidden="true">€</span>
      </div>
      <FieldMessages id={id} error={error} hint={hint} />
    </div>
  );
};

interface SelectFieldProps<T extends string> extends BaseFieldProps {
  value: T | '';
  options: Array<{ value: T; label: string }>;
  onChange: (value: T) => void;
  placeholder?: string;
}

export const SelectField = <T extends string>({ label, error, hint, value, options, onChange, placeholder = 'Choisir…' }: SelectFieldProps<T>) => {
  const id = useId();

  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      <select
        id={id}
        value={value}
        onChange={(event) => onChange(event.target.value as T)}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy(id, error, hint)}
      >
        <option value="" disabled>
          {placeholder}
        </option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      <FieldMessages id={id} error={error} hint={hint} />
    </div>
  );
};

interface ChoiceCardsProps<T extends string> extends BaseFieldProps {
  value: T | '';
  options: Array<{ value: T; label: string; icon?: ReactNode; description?: string }>;
  onChange: (value: T) => void;
  columns?: 2 | 3 | 5;
}

/** Choix exclusif présenté en cartes (boutons radio natifs, navigables au clavier). */
export const ChoiceCards = <T extends string>({ label, error, hint, value, options, onChange, columns = 3 }: ChoiceCardsProps<T>) => {
  const id = useId();

  return (
    <fieldset className="field choice-field" aria-describedby={describedBy(id, error, hint)}>
      <legend>{label}</legend>
      <div className={`choice-cards cols-${columns}`}>
        {options.map((option) => (
          <label key={option.value} className={`choice-card${value === option.value ? ' is-selected' : ''}`}>
            <input type="radio" name={id} value={option.value} checked={value === option.value} onChange={() => onChange(option.value)} />
            {option.icon ? <span className="choice-icon">{option.icon}</span> : null}
            <span className="choice-label">{option.label}</span>
            {option.description ? <span className="choice-description">{option.description}</span> : null}
          </label>
        ))}
      </div>
      <FieldMessages id={id} error={error} hint={hint} />
    </fieldset>
  );
};


export const CheckboxField = ({ label, checked, onChange, error }: { label: ReactNode; checked: boolean; onChange: (checked: boolean) => void; error?: string }) => {
  const id = useId();

  return (
    <div className="field checkbox-field">
      <label htmlFor={id}>
        <input id={id} type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} aria-invalid={error ? true : undefined} aria-describedby={error ? `${id}-error` : undefined} />
        <span>{label}</span>
      </label>
      <FieldMessages id={id} error={error} />
    </div>
  );
};
