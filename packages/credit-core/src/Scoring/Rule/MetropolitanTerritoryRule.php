<?php

declare(strict_types=1);

namespace Credit\Core\Scoring\Rule;

use Credit\Core\DTO\Response\RuleExecutionDto;
use Credit\Core\Scoring\Model\RuleContext;

/**
 * Codes postaux 01000 à 95999 (France métropolitaine et Corse), hors Monaco (98000) et DROM-COM (97xxx, 98xxx).
 */
final class MetropolitanTerritoryRule extends AbstractAdmissionRule
{
    public const string PATTERN = '/^(?:0[1-9]|[1-8]\d|9[0-5])\d{3}$/';

    public function getCode(): string
    {
        return 'METROPOLITAN_TERRITORY';
    }

    public function evaluate(RuleContext $context): RuleExecutionDto
    {
        if (1 === preg_match(self::PATTERN, $context->request->postalCode)) {
            return $this->pass();
        }

        return $this->fail('Notre service est réservé aux résidents de France métropolitaine et de Corse.', 'postalCode');
    }
}
