import { OtpForm } from 'credit-front-core';
import { Navigate, useNavigate, useSearchParams } from 'react-router';
import { clientApi } from '../config';

/**
 * /code : saisie du code. Reçoit aussi le cas OTP_REQUIRED venu du tunnel (?email=…&dossierId=…) :
 * le code envoyé est lié à ce dossier, qui devient visible une fois le code vérifié.
 */
export const ClientOtpPage = () => {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const email = params.get('email') ?? '';
  const dossierId = params.get('dossierId');

  if (email === '') {
    return <Navigate to="/connexion" replace />;
  }

  const verify = async (code: string) => {
    const result = await clientApi.verifyOtp(email, code);
    const target = result.dossierId ?? dossierId;
    navigate(target ? `/dossiers/${target}` : '/', { replace: true });
  };

  return (
    <div className="container">
      {dossierId ? (
        <p className="alert alert-info narrow center-block">
          Votre demande est enregistrée. Pour la retrouver dans votre espace, saisissez le code que nous venons de vous envoyer.
        </p>
      ) : null}
      <OtpForm
        email={email}
        onVerify={verify}
        onResend={() => clientApi.requestOtp(email, dossierId).then(() => undefined)}
        onChangeEmail={() => navigate('/connexion')}
      />
    </div>
  );
};
