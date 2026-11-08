/**
 * Contrôles synchrones du tunnel (l'API refait les mêmes contrôles et répond 422 sinon).
 */

/** France métropolitaine et Corse : 01000 à 95999 (hors Monaco 98000 et DROM-COM 97xxx / 98xxx). */
export const METROPOLITAN_POSTAL_CODE = /^(?:0[1-9]|[1-8]\d|9[0-5])\d{3}$/;

/** Mobile français 06 / 07 (format national). */
export const FRENCH_MOBILE = /^0[67]\d{8}$/;

export const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const MIN_AGE = 18;
export const MAX_AGE = 80;

export const isMetropolitanPostalCode = (value: string): boolean => METROPOLITAN_POSTAL_CODE.test(value.trim());

/** « +33 6 12 34 56 78 », « 06.12.34.56.78 » → « 0612345678 ». */
export const normalizeMobile = (value: string): string => {
  const digits = value.replace(/[^\d+]/g, '');

  return digits.startsWith('+33') ? `0${digits.slice(3)}` : digits.startsWith('0033') ? `0${digits.slice(4)}` : digits;
};

export const isFrenchMobile = (value: string): boolean => FRENCH_MOBILE.test(normalizeMobile(value));

/** Âge en années révolues à la date du jour (null si la date est invalide ou future). */
export const ageFromBirthDate = (birthDate: string, today = new Date()): number | null => {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(birthDate);
  if (!match) return null;

  const [year, month, day] = [Number(match[1]), Number(match[2]), Number(match[3])];
  const birth = new Date(year, month - 1, day);
  if (birth.getMonth() !== month - 1 || birth > today) return null;

  let age = today.getFullYear() - year;
  if (today.getMonth() < month - 1 || (today.getMonth() === month - 1 && today.getDate() < day)) {
    age -= 1;
  }

  return age;
};

export const isAllowedAge = (birthDate: string): boolean => {
  const age = ageFromBirthDate(birthDate);

  return age !== null && age >= MIN_AGE && age <= MAX_AGE;
};

/** Champ mois (AAAA-MM) dans le passé ou le mois en cours. */
export const isPastMonth = (value: string): boolean => {
  const match = /^(\d{4})-(\d{2})$/.exec(value);
  if (!match) return false;

  const now = new Date();
  const current = now.getFullYear() * 12 + now.getMonth();

  return Number(match[1]) * 12 + Number(match[2]) - 1 <= current && Number(match[1]) > 1900;
};
