<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleSeverityEnum;
use Credit\Core\Enum\RuleStageEnum;

/**
 * Base des règles de recevabilité (étape ADMISSION) : un échec renvoie une erreur 422 avant tout calcul.
 */
abstract class AbstractAdmissionRule implements ScoringRuleInterface
{
    final public function getStage(): RuleStageEnum
    {
        return RuleStageEnum::ADMISSION;
    }

    final public function severity(): RuleSeverityEnum
    {
        return RuleSeverityEnum::STRUCTURAL;
    }

    public function getRejectionReason(bool $failsForAllOffers): string
    {
        return 'Votre demande n\'est pas recevable.';
    }

    protected function pass(): RuleExecutionDto
    {
        return RuleExecutionDto::pass($this->getCode(), $this->severity());
    }

    protected function fail(string $message, string $field): RuleExecutionDto
    {
        return RuleExecutionDto::fail($this->getCode(), $this->severity(), $message, null, $field);
    }
}
