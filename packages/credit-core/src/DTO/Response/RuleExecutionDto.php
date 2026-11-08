<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

use Credit\Core\Enum\RuleSeverityEnum;

/**
 * Résultat d'une règle : passée ou échouée, avec sa sévérité effective
 * (une règle ADJUSTABLE qui échoue encore dans la meilleure variante du produit devient STRUCTURAL).
 */
final readonly class RuleExecutionDto
{
    public function __construct(
        public string $ruleCode,
        public bool $passed,
        public RuleSeverityEnum $severity,
        public string $message,
        public ?string $offerCode = null,
        /** Champ du payload concerné (erreurs 422 de recevabilité). */
        public ?string $field = null,
    ) {}

    public static function pass(string $ruleCode, RuleSeverityEnum $severity, ?string $offerCode = null): self
    {
        return new self($ruleCode, true, $severity, '', $offerCode);
    }

    public static function fail(string $ruleCode, RuleSeverityEnum $severity, string $message, ?string $offerCode = null, ?string $field = null): self
    {
        return new self($ruleCode, false, $severity, $message, $offerCode, $field);
    }

    public function withSeverity(RuleSeverityEnum $severity): self
    {
        return new self($this->ruleCode, $this->passed, $severity, $this->message, $this->offerCode, $this->field);
    }
}
