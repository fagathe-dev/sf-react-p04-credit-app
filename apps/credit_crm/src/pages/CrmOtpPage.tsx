import { OtpForm } from 'credit-front-core';
import { Navigate, useNavigate, useSearchParams } from 'react-router';
import { crmApi } from '../config';

/** /code : saisie du code reçu par e-mail. */
export const CrmOtpPage = () => {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const email = params.get('email') ?? '';

  if (email === '') {
    return <Navigate to="/connexion" replace />;
  }

  return (
    <div className="container">
      <OtpForm
        email={email}
        onVerify={async (code) => {
          await crmApi.verifyOtp(email, code);
          navigate('/', { replace: true });
        }}
        onResend={() => crmApi.requestOtp(email).then(() => undefined)}
        onChangeEmail={() => navigate('/connexion')}
      />
    </div>
  );
};
