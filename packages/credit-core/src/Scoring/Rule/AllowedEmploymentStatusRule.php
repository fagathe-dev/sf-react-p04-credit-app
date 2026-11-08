<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Scoring\Model\RuleContext;

/**
 * Le contrat de l'emprunteur est accepté par le produit.
 */
final class AllowedEmploymentStatusRule extends AbstractProductRule
{
    public function getCode(): string
    {
        return 'ALLOWED_EMPLOYMENT_STATUS';
    }

    public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::STRUCTURAL;
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        $status = $context->requireProfile()->employmentStatus;

        if ($context->requireOffer()->criteria->acceptsEmploymentStatus($status)) {
            return $this->pass($context);
        }

        return $this->fail($context, \sprintf('Votre situation professionnelle (%s) n\'est pas acceptée par ce produit.', $status->label()));
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return $failsForAllOffers
            ? 'Aucun de nos partenaires n\'accepte votre type de contrat pour ce projet.'
            : 'Votre type de contrat n\'est pas accepté par une partie des offres de ce projet.';
    }
}
