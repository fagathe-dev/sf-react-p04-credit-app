<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Enum\RuleStageEnum;
use Credit\Core\Scoring\Model\RuleContext;

/**
 * Base des règles d'éligibilité à un produit (étape PRODUCT).
 */
abstract class AbstractProductRule implements ScoringRuleInterface
{
    final public function getStage(): RuleStageEnum
    {
        return RuleStageEnum::PRODUCT;
    }

    protected function pass(RuleContext $context): RuleExecutionDto
    {
        return RuleExecutionDto::pass($this->getCode(), $this->severity(), $context->requireOffer()->offerCode);
    }

    protected function fail(RuleContext $context, string $message): RuleExecutionDto
    {
        return RuleExecutionDto::fail($this->getCode(), $this->severity(), $message, $context->requireOffer()->offerCode);
    }
}
