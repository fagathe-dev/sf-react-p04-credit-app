import {
  CALLBACK_PRIORITY_LABELS,
  CALLBACK_PRIORITY_SHORT_LABELS,
  CALLBACK_STATUS_LABELS,
  DECISION_LABELS,
  formatBpsToPercent,
  formatCentsToEuros,
  formatDateTime,
  formatPhone,
  PROJECT_LABELS,
  WORKFLOW_STATUS_LABELS,
  type CallbackPriority,
  type CallbackStatus,
  type CrmCallbackItem,
  type DossierWorkflowStatus,
  type Paginated,
  type PriorityFilter,
  type ScoringDecision,
} from 'credit-front-core';
import { useId } from 'react';
import { Link } from 'react-router';

export interface CallbackTableFilters {
  priority: PriorityFilter;
  decision: ScoringDecision | '';
  callbackStatus: CallbackStatus | '';
  workflowStatus: DossierWorkflowStatus | '';
  page: number;
}

interface CallbackTableProps {
  data: Paginated<CrmCallbackItem> | null;
  filters: CallbackTableFilters;
  onFiltersChange: (filters: CallbackTableFilters) => void;
  isLoading: boolean;
}

const PRIORITY_OPTIONS: Array<{ value: PriorityFilter; label: string }> = [
  { value: '', label: 'File d\'appels (P1 à P3)' },
  ...(Object.keys(CALLBACK_PRIORITY_LABELS) as CallbackPriority[]).map((priority) => ({ value: priority as PriorityFilter, label: CALLBACK_PRIORITY_LABELS[priority] })),
  { value: 'ALL', label: 'Tous les dossiers' },
];

interface SelectFilterProps<T extends string> {
  label: string;
  value: T;
  options: Array<{ value: T; label: string }>;
  onChange: (value: T) => void;
}

const SelectFilter = <T extends string>({ label, value, options, onChange }: SelectFilterProps<T>) => {
  const id = useId();

  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      <select id={id} value={value} onChange={(event) => onChange(event.target.value as T)}>
        {options.map((option) => (
          <option key={option.value || 'default'} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
};

const withAll = <T extends string>(labels: Record<T, string>, allLabel: string): Array<{ value: T | ''; label: string }> => [
  { value: '', label: allLabel },
  ...(Object.keys(labels) as T[]).map((value) => ({ value, label: labels[value] })),
];

/**
 * File de rappels : P1, P1 bis, P2, P3, puis les plus anciens d'abord (priorité calculée par l'API).
 */
export const CallbackTable = ({ data, filters, onFiltersChange, isLoading }: CallbackTableProps) => (
  <section className="callback-queue">
    <div className="filters">
      <SelectFilter label="Priorité" value={filters.priority} options={PRIORITY_OPTIONS} onChange={(priority) => onFiltersChange({ ...filters, priority, page: 1 })} />
      <SelectFilter label="Avancement" value={filters.workflowStatus} options={withAll(WORKFLOW_STATUS_LABELS, 'Tous')} onChange={(workflowStatus) => onFiltersChange({ ...filters, workflowStatus, page: 1 })} />
      <SelectFilter label="Décision" value={filters.decision} options={withAll(DECISION_LABELS, 'Toutes')} onChange={(decision) => onFiltersChange({ ...filters, decision, page: 1 })} />
      <SelectFilter label="Suivi d'appel" value={filters.callbackStatus} options={withAll(CALLBACK_STATUS_LABELS, 'Tous')} onChange={(callbackStatus) => onFiltersChange({ ...filters, callbackStatus, page: 1 })} />
      {data ? (
        <p className="muted small filters-total" aria-live="polite">
          {data.total} dossier{data.total > 1 ? 's' : ''}
        </p>
      ) : null}
    </div>

    <div className="table-wrapper">
      <table className="data-table" aria-busy={isLoading}>
        <thead>
          <tr>
            <th scope="col">Priorité</th>
            <th scope="col">Client</th>
            <th scope="col">Projet</th>
            <th scope="col">Avancement</th>
            <th scope="col">Suivi</th>
            <th scope="col">Résultat du</th>
          </tr>
        </thead>
        <tbody>
          {data && data.items.length === 0 ? (
            <tr>
              <td colSpan={6} className="muted">
                Aucun dossier ne correspond à ces filtres.
              </td>
            </tr>
          ) : null}
          {data?.items.map((item) => (
            <tr key={item.dossierId}>
              <td>
                <span className={`priority-badge priority-${item.priority.toLowerCase()}`} title={CALLBACK_PRIORITY_LABELS[item.priority]}>
                  {CALLBACK_PRIORITY_SHORT_LABELS[item.priority]}
                </span>
              </td>
              <td>
                <Link to={`/dossiers/${item.dossierId}`}>
                  <strong>{item.clientName}</strong>
                </Link>
                <span className="cell-sub">{formatPhone(item.phone)}</span>
                <span className="cell-reason">{item.priorityReason}</span>
              </td>
              <td>
                {PROJECT_LABELS[item.projectType]}
                <span className="cell-sub">
                  {formatCentsToEuros(item.requestedAmountCents, { withCents: false })} · {item.durationMonths} mois · {formatBpsToPercent(item.debtRatioAfterBps, 1)}
                </span>
              </td>
              <td>
                <span className={`badge badge-${item.workflowStatus.toLowerCase()}`}>{WORKFLOW_STATUS_LABELS[item.workflowStatus]}</span>
              </td>
              <td>
                {item.callbackStatus ? CALLBACK_STATUS_LABELS[item.callbackStatus] : '—'}
                {item.advisorName ? <span className="cell-sub">{item.advisorName}</span> : null}
              </td>
              <td>{formatDateTime(item.submittedAt)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>

    {data && data.pages > 1 ? (
      <nav className="pagination" aria-label="Pagination">
        <button type="button" className="btn btn-outline" disabled={filters.page <= 1} onClick={() => onFiltersChange({ ...filters, page: filters.page - 1 })}>
          Précédent
        </button>
        <span>
          Page {data.page} sur {data.pages}
        </span>
        <button type="button" className="btn btn-outline" disabled={filters.page >= data.pages} onClick={() => onFiltersChange({ ...filters, page: filters.page + 1 })}>
          Suivant
        </button>
      </nav>
    ) : null}
  </section>
);
