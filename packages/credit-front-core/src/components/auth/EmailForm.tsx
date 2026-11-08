import { useId, useState, type FormEvent } from 'react';
import { getErrorMessage } from '../../api/httpClient';
import { MailIcon } from '../icons/ProjectIconsSvg';

interface EmailFormProps {
  /** Demande du code : POST …/otp/request (répond toujours 204). */
  onSubmit: (email: string) => Promise<void>;
  initialEmail?: string;
  title?: string;
  description?: string;
  submitLabel?: string;
}

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Saisie de l'adresse e-mail et demande d'un code de connexion.
 */
export const EmailForm = ({
  onSubmit,
  initialEmail = '',
  title = 'Connexion',
  description = 'Saisissez votre adresse e-mail : nous vous envoyons un code de connexion à usage unique.',
  submitLabel = 'Recevoir mon code',
}: EmailFormProps) => {
  const inputId = useId();
  const [email, setEmail] = useState(initialEmail);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const value = email.trim().toLowerCase();

    if (!EMAIL_PATTERN.test(value)) {
      setError('Saisissez une adresse e-mail valide.');
      return;
    }

    setError(null);
    setIsSubmitting(true);
    try {
      await onSubmit(value);
    } catch (submitError) {
      setError(getErrorMessage(submitError));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form className="auth-card" onSubmit={handleSubmit} noValidate>
      <span className="auth-icon">
        <MailIcon />
      </span>
      <h1>{title}</h1>
      <p className="muted">{description}</p>

      <div className="field">
        <label htmlFor={inputId}>Adresse e-mail</label>
        <input
          id={inputId}
          type="email"
          inputMode="email"
          autoComplete="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? `${inputId}-error` : undefined}
          required
        />
        {error ? (
          <p className="field-error" id={`${inputId}-error`}>
            {error}
          </p>
        ) : null}
      </div>

      <button type="submit" className="btn btn-primary btn-block" disabled={isSubmitting}>
        {isSubmitting ? 'Envoi en cours…' : submitLabel}
      </button>
    </form>
  );
};
