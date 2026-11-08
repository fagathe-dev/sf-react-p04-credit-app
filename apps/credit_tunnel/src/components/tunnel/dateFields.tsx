import { useId, useState } from 'react';

/**
 * Saisie de dates par listes déroulantes (pas de sélecteur natif) :
 * - MonthYearField : mois + année, valeur « AAAA-MM » (date d'entrée, début de contrat) ;
 * - DateSelectField : jour + mois + année, valeur « AAAA-MM-JJ » (date de naissance).
 * Tant que la date est incomplète, la valeur transmise est vide (la validation de l'étape le signale).
 */
const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

const pad = (value: number): string => String(value).padStart(2, '0');

/** Années décroissantes de `from` à `to` inclus. */
const yearRange = (from: number, to: number): number[] => Array.from({ length: from - to + 1 }, (_, index) => from - index);

const daysInMonth = (year: number, month: number): number => new Date(year, month, 0).getDate();

interface BaseDateFieldProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  error?: string;
  hint?: string;
  /** Année la plus récente proposée (par défaut : l'année en cours). */
  latestYear?: number;
  /** Année la plus ancienne proposée. */
  earliestYear: number;
}

const Messages = ({ id, error, hint }: { id: string; error?: string; hint?: string }) => (
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

const describedBy = (id: string, error?: string, hint?: string): string | undefined =>
  [error ? `${id}-error` : null, hint ? `${id}-hint` : null].filter(Boolean).join(' ') || undefined;

const MonthSelect = ({ value, onChange, invalid, describedById }: { value: string; onChange: (value: string) => void; invalid: boolean; describedById?: string }) => (
  <select aria-label="Mois" value={value} onChange={(event) => onChange(event.target.value)} aria-invalid={invalid || undefined} aria-describedby={describedById}>
    <option value="" disabled>
      Mois
    </option>
    {MONTHS.map((name, index) => (
      <option key={name} value={pad(index + 1)}>
        {name}
      </option>
    ))}
  </select>
);

const YearSelect = ({ value, years, onChange, invalid, describedById }: { value: string; years: number[]; onChange: (value: string) => void; invalid: boolean; describedById?: string }) => (
  <select aria-label="Année" value={value} onChange={(event) => onChange(event.target.value)} aria-invalid={invalid || undefined} aria-describedby={describedById}>
    <option value="" disabled>
      Année
    </option>
    {years.map((year) => (
      <option key={year} value={String(year)}>
        {year}
      </option>
    ))}
  </select>
);

export const MonthYearField = ({ label, value, onChange, error, hint, latestYear = new Date().getFullYear(), earliestYear }: BaseDateFieldProps) => {
  const id = useId();
  const match = /^(\d{4})-(\d{2})$/.exec(value);
  const [year, setYear] = useState(match?.[1] ?? '');
  const [month, setMonth] = useState(match?.[2] ?? '');
  const ariaDescribedBy = describedBy(id, error, hint);

  const update = (nextYear: string, nextMonth: string) => {
    setYear(nextYear);
    setMonth(nextMonth);
    onChange(nextYear !== '' && nextMonth !== '' ? `${nextYear}-${nextMonth}` : '');
  };

  return (
    <fieldset className="field date-field">
      <legend>{label}</legend>
      <div className="date-selects cols-2">
        <MonthSelect value={month} onChange={(next) => update(year, next)} invalid={Boolean(error)} describedById={ariaDescribedBy} />
        <YearSelect value={year} years={yearRange(latestYear, earliestYear)} onChange={(next) => update(next, month)} invalid={Boolean(error)} describedById={ariaDescribedBy} />
      </div>
      <Messages id={id} error={error} hint={hint} />
    </fieldset>
  );
};

export const DateSelectField = ({ label, value, onChange, error, hint, latestYear = new Date().getFullYear(), earliestYear }: BaseDateFieldProps) => {
  const id = useId();
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  const [year, setYear] = useState(match?.[1] ?? '');
  const [month, setMonth] = useState(match?.[2] ?? '');
  const [day, setDay] = useState(match?.[3] ?? '');
  const ariaDescribedBy = describedBy(id, error, hint);
  const maxDay = year !== '' && month !== '' ? daysInMonth(Number(year), Number(month)) : 31;

  const update = (nextYear: string, nextMonth: string, nextDay: string) => {
    // Jour ramené au dernier jour du mois (ex. 31 → 30 en avril)
    const limit = nextYear !== '' && nextMonth !== '' ? daysInMonth(Number(nextYear), Number(nextMonth)) : 31;
    const safeDay = nextDay !== '' && Number(nextDay) > limit ? pad(limit) : nextDay;
    setYear(nextYear);
    setMonth(nextMonth);
    setDay(safeDay);
    onChange(nextYear !== '' && nextMonth !== '' && safeDay !== '' ? `${nextYear}-${nextMonth}-${safeDay}` : '');
  };

  return (
    <fieldset className="field date-field">
      <legend>{label}</legend>
      <div className="date-selects cols-3">
        <select aria-label="Jour" value={day} onChange={(event) => update(year, month, event.target.value)} aria-invalid={Boolean(error) || undefined} aria-describedby={ariaDescribedBy}>
          <option value="" disabled>
            Jour
          </option>
          {Array.from({ length: maxDay }, (_, index) => (
            <option key={index + 1} value={pad(index + 1)}>
              {index + 1}
            </option>
          ))}
        </select>
        <MonthSelect value={month} onChange={(next) => update(year, next, day)} invalid={Boolean(error)} describedById={ariaDescribedBy} />
        <YearSelect value={year} years={yearRange(latestYear, earliestYear)} onChange={(next) => update(next, month, day)} invalid={Boolean(error)} describedById={ariaDescribedBy} />
      </div>
      <Messages id={id} error={error} hint={hint} />
    </fieldset>
  );
};
