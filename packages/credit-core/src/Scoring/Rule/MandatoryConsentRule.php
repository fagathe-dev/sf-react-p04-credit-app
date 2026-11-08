<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Scoring\Model\RuleContext;

/**
 * Consentements obligatoires : transmission aux partenaires (toujours),
 * création de l'espace client (nouveau client uniquement).
 */
final class MandatoryConsentRule extends AbstractAdmissionRule
{
    public function getCode(): string
    {
        return 'MANDATORY_CONSENT';
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        if (!$context->request->consentPartnerTransmission) {
            return $this->fail('Le consentement de transmission de vos données aux partenaires est obligatoire pour obtenir vos offres.', 'consentPartnerTransmission');
        }

        if ($context->isNewClient && !$context->request->consentClientSpaceCreation) {
            return $this->fail('La création de votre espace client doit être acceptée pour enregistrer votre demande.', 'consentClientSpaceCreation');
        }

        return $this->pass();
    }
}
