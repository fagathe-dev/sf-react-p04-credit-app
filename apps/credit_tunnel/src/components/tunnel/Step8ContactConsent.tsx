import { CheckboxField, TextField } from './fields';
import type { StepProps } from './stepProps';

export const Step8ContactConsent = ({ values, errors, onChange }: StepProps) => (
  <>
    <div className="field-row">
      <TextField
        label="Adresse e-mail"
        type="email"
        inputMode="email"
        autoComplete="email"
        value={values.email}
        error={errors.email}
        hint="Elle identifie votre espace client : vous y retrouverez votre résultat."
        onChange={(email) => onChange({ email })}
      />
      <TextField
        label="Téléphone mobile"
        type="tel"
        inputMode="tel"
        autoComplete="tel-national"
        value={values.phone}
        error={errors.phone}
        hint="Mobile français commençant par 06 ou 07."
        onChange={(phone) => onChange({ phone })}
      />
    </div>

    <CheckboxField
      checked={values.consentPartnerTransmission}
      error={errors.consentPartnerTransmission}
      onChange={(consentPartnerTransmission) => onChange({ consentPartnerTransmission })}
      label="J'accepte que mes données soient transmises aux établissements financiers partenaires afin d'obtenir mes offres de crédit personnalisées. (obligatoire)"
    />
    <CheckboxField
      checked={values.consentAdvisorCallback}
      onChange={(consentAdvisorCallback) => onChange({ consentAdvisorCallback })}
      label="J'accepte d'être rappelé par un conseiller, pour ajuster mon dossier si c'est possible ou pour me proposer d'autres solutions de financement. (facultatif)"
    />
  </>
);
