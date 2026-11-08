import { formatDuration, useCountdown } from 'core-react';
import { useEffect, useId, useState, type FormEvent } from 'react';
import { getErrorMessage, isTooManyRequests, isUnauthorized } from '../../api/httpClient';

interface OtpFormProps {
  email: string;
  /** Vérification : POST …/otp/verify. Rejette en 401 (code faux ou expiré) ou 429 (trop d'essais). */
  onVerify: (code: string) => Promise<void>;
  /** Renvoi d'un code : POST …/otp/request. */
  onResend: () => Promise<void>;
  /** Délai entre deux envois (OTP_RESEND_DELAY_SECONDS côté API). */
  resendDelaySeconds?: number;
  /** Un code vient d'être envoyé (arrivée depuis le formulaire e-mail ou le tunnel). */
  codeJustSent?: boolean;
  onChangeEmail?: () => void;
}

/**
 * Saisie du code à 6 chiffres reçu par e-mail, avec renvoi limité dans le temps.
 */
export const OtpForm = ({ email, onVerify, onResend, resendDelaySeconds = 60, codeJustSent = true, onChangeEmail }: OtpFormProps) => {
  const inputId = useId();
  const [code, setCode] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const countdown = useCountdown();
  const { start } = countdown;

  useEffect(() => {
    if (codeJustSent) start(resendDelaySeconds);
  }, [codeJustSent, resendDelaySeconds, start]);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!/^\d{6}$/.test(code)) {
      setError('Le code comporte 6 chiffres.');
      return;
    }

    setError(null);
    setNotice(null);
    setIsSubmitting(true);
    try {
      await onVerify(code);
    } catch (verifyError) {
      if (isTooManyRequests(verifyError)) {
        setError('Trop d\'essais : ce code n\'est plus valable. Demandez un nouveau code.');
      } else if (isUnauthorized(verifyError)) {
        setError('Code invalide ou expiré.');
      } else {
        setError(getErrorMessage(verifyError));
      }
      setCode('');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleResend = async () => {
    setError(null);
    try {
      await onResend();
      setNotice('Si un compte correspond à cette adresse, un nouveau code vient d\'être envoyé.');
      start(resendDelaySeconds);
    } catch (resendError) {
      setError(getErrorMessage(resendError));
    }
  };

  return (
    <form className="auth-card" onSubmit={handleSubmit} noValidate>
      <h1>Saisissez votre code</h1>
      <p className="muted">
        Un code à 6 chiffres a été envoyé à <strong>{email}</strong>. Il est valable quelques minutes et ne sert qu'une fois.
      </p>

      <div className="field">
        <label htmlFor={inputId}>Code de connexion</label>
        <input
          id={inputId}
          className="otp-input"
          type="text"
          inputMode="numeric"
          autoComplete="one-time-code"
          pattern="\d{6}"
          maxLength={6}
          value={code}
          onChange={(event) => setCode(event.target.value.replace(/\D/g, '').slice(0, 6))}
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? `${inputId}-error` : undefined}
          autoFocus
        />
        {error ? (
          <p className="field-error" id={`${inputId}-error`} role="alert">
            {error}
          </p>
        ) : null}
        {notice ? (
          <p className="field-hint" role="status">
            {notice}
          </p>
        ) : null}
      </div>

      <button type="submit" className="btn btn-primary btn-block" disabled={isSubmitting || code.length !== 6}>
        {isSubmitting ? 'Vérification…' : 'Valider'}
      </button>

      <div className="auth-links">
        <button type="button" className="btn-link" onClick={handleResend} disabled={countdown.isRunning}>
          {countdown.isRunning ? `Renvoyer un code dans ${formatDuration(countdown.secondsLeft)}` : 'Renvoyer un code'}
        </button>
        {onChangeEmail ? (
          <button type="button" className="btn-link" onClick={onChangeEmail}>
            Changer d'adresse e-mail
          </button>
        ) : null}
      </div>
    </form>
  );
};
