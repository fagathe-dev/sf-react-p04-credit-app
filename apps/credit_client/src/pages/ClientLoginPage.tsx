import { EmailForm } from 'credit-front-core';
import { useNavigate, useSearchParams } from 'react-router';
import { clientApi } from '../config';

/** /connexion : saisie de l'e-mail, demande du code. */
export const ClientLoginPage = () => {
  const navigate = useNavigate();
  const [params] = useSearchParams();

  const requestCode = async (email: string) => {
    await clientApi.requestOtp(email);
    navigate(`/code?${new URLSearchParams({ email }).toString()}`);
  };

  return (
    <div className="container">
      <EmailForm title="Connexion à mon espace" onSubmit={requestCode} initialEmail={params.get('email') ?? ''} />
    </div>
  );
};
