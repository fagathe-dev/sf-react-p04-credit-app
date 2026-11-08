import { EmailForm } from 'credit-front-core';
import { useNavigate } from 'react-router';
import { crmApi } from '../config';

/** /connexion : seuls les conseillers actifs reçoivent un code. */
export const CrmLoginPage = () => {
  const navigate = useNavigate();

  const requestCode = async (email: string) => {
    await crmApi.requestOtp(email);
    navigate(`/code?${new URLSearchParams({ email }).toString()}`);
  };

  return (
    <div className="container">
      <EmailForm title="Espace conseillers" description="Saisissez votre adresse professionnelle : vous recevrez un code de connexion." onSubmit={requestCode} />
    </div>
  );
};
