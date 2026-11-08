import { formatDate, formatPersonName, formatPhone, type ClientProfile } from 'credit-front-core';

/** Informations du client (la modification du profil est hors périmètre). */
export const ProfileCard = ({ profile }: { profile: ClientProfile }) => (
  <section className="card profile-card">
    <h2>Mes informations</h2>
    <dl className="facts-list">
      <div>
        <dt>Nom</dt>
        <dd>
          {formatPersonName(profile.civility, profile.firstName, profile.lastName)}
        </dd>
      </div>
      <div>
        <dt>Date de naissance</dt>
        <dd>{formatDate(profile.birthDate)}</dd>
      </div>
      <div>
        <dt>E-mail</dt>
        <dd>{profile.email}</dd>
      </div>
      <div>
        <dt>Mobile</dt>
        <dd>{formatPhone(profile.phone)}</dd>
      </div>
      <div>
        <dt>Espace créé le</dt>
        <dd>{formatDate(profile.createdAt)}</dd>
      </div>
    </dl>
  </section>
);
