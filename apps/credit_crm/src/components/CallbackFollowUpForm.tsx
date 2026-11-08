import {
  CALLBACK_STATUS_LABELS,
  formatDateTime,
  getErrorMessage,
  getViolations,
  isUnauthorized,
  WORKFLOW_STATUS_LABELS,
  type CallbackStatus,
  type CrmDossierDetail,
  type DossierWorkflowStatus,
} from 'credit-front-core';
import { useId, useState, type FormEvent } from 'react';
import { crmApi } from '../config';
import { useCrm } from './crmContext';

interface CallbackFollowUpFormProps {
  detail: CrmDossierDetail;
  onSaved: (detail: CrmDossierDetail) => void;
}

/**
 * Statut d'appel, note du conseiller et, pour une offre choisie, validation ou refus du dossier
 * (PATCH /api/v1/crm/dossiers/{id}/callback).
 */
export const CallbackFollowUpForm = ({ detail, onSaved }: CallbackFollowUpFormProps) => {
  const { handleUnauthorized } = useCrm();
  const statusId = useId();
  const workflowId = useId();
  const noteId = useId();
  const [status, setStatus] = useState<CallbackStatus>(detail.callbackStatus ?? 'TO_CALL');
  const [workflow, setWorkflow] = useState<DossierWorkflowStatus | ''>('');
  const [note, setNote] = useState(detail.advisorNote ?? '');
  const [isSaving, setIsSaving] = useState(false);
  const [feedback, setFeedback] = useState<{ type: 'success' | 'danger'; message: string } | null>(null);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setIsSaving(true);
    setFeedback(null);
    try {
      const updated = await crmApi.updateCallback(detail.dossier.id, {
        callbackStatus: status,
        advisorNote: note.trim() === '' ? null : note,
        workflowStatus: workflow === '' ? null : workflow,
      });
      onSaved(updated);
      setFeedback({ type: 'success', message: 'Suivi enregistré.' });
    } catch (saveError) {
      if (isUnauthorized(saveError)) {
        handleUnauthorized();
        return;
      }
      const violations = Object.values(getViolations(saveError));
      setFeedback({ type: 'danger', message: violations[0] ?? getErrorMessage(saveError) });
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <form className="card follow-up" onSubmit={handleSubmit} noValidate>
      <h2>Suivi d'appel</h2>
      {detail.callbackUpdatedAt ? (
        <p className="muted small">
          Mis à jour le {formatDateTime(detail.callbackUpdatedAt)}
          {detail.advisorName ? ` par ${detail.advisorName}` : ''}
        </p>
      ) : null}
      <div className="field">
        <label htmlFor={statusId}>Statut</label>
        <select id={statusId} value={status} onChange={(event) => setStatus(event.target.value as CallbackStatus)}>
          {(Object.keys(CALLBACK_STATUS_LABELS) as CallbackStatus[]).map((value) => (
            <option key={value} value={value}>
              {CALLBACK_STATUS_LABELS[value]}
            </option>
          ))}
        </select>
      </div>
      {detail.workflowTransitions.length > 0 ? (
        <div className="field">
          <label htmlFor={workflowId}>Décision sur l'offre choisie</label>
          <select id={workflowId} value={workflow} onChange={(event) => setWorkflow(event.target.value as DossierWorkflowStatus | '')}>
            <option value="">Pas de changement ({WORKFLOW_STATUS_LABELS[detail.dossier.workflowStatus]})</option>
            {detail.workflowTransitions.map((value) => (
              <option key={value} value={value}>
                {WORKFLOW_STATUS_LABELS[value]}
              </option>
            ))}
          </select>
        </div>
      ) : null}
      <div className="field">
        <label htmlFor={noteId}>Note du conseiller</label>
        <textarea id={noteId} value={note} maxLength={5000} onChange={(event) => setNote(event.target.value)} />
      </div>
      {feedback ? (
        <p className={`alert alert-${feedback.type}`} role="status">
          {feedback.message}
        </p>
      ) : null}
      <button type="submit" className="btn btn-primary btn-block" disabled={isSaving}>
        {isSaving ? 'Enregistrement…' : 'Enregistrer le suivi'}
      </button>
    </form>
  );
};
