import { ClientStatus, type ComparisonSubmission } from 'credit-front-core';
import { CLIENT_URL } from '../config';

/**
 * URL de credit_client après POST /api/v1/compare (section 7.2 de la spec) :
 * - CREATED / AUTHENTICATED : le dossier, session déjà ouverte ;
 * - OTP_REQUIRED : saisie du code envoyé par e-mail, puis le dossier.
 */
export const buildClientRedirect = (submission: ComparisonSubmission, email: string): string => {
  if (submission.clientStatus === ClientStatus.OTP_REQUIRED) {
    const params = new URLSearchParams({ email, dossierId: submission.dossierId });

    return `${CLIENT_URL}/code?${params.toString()}`;
  }

  return `${CLIENT_URL}/dossiers/${encodeURIComponent(submission.dossierId)}`;
};
