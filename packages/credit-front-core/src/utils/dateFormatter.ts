/** Dates ISO 8601 de l'API → affichage français. */

const dateFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
const dateTimeFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const parse = (iso: string): Date | null => {
  const date = new Date(iso);

  return Number.isNaN(date.getTime()) ? null : date;
};

/** « 5 novembre 2026 » */
export const formatDate = (iso: string | null | undefined): string => {
  const date = iso ? parse(iso) : null;

  return date ? dateFormatter.format(date) : '—';
};

/** « 5 novembre 2026 à 17:08 » */
export const formatDateTime = (iso: string | null | undefined): string => {
  const date = iso ? parse(iso) : null;

  return date ? dateTimeFormatter.format(date) : '—';
};

/** 0612345678 → « 06 12 34 56 78 » */
export const formatPhone = (phone: string | null | undefined): string => {
  const digits = (phone ?? '').replace(/\D/g, '');

  return digits.length === 10 ? digits.replace(/(\d{2})(?=\d)/g, '$1 ') : (phone ?? '');
};
