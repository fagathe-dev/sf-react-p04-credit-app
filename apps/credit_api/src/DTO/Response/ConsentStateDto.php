<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * État des consentements d'un client (chacun daté, jamais un simple booléen en base).
 */
final readonly class ConsentStateDto
{
    public function __construct(
        public string $clientSpaceCreatedAt,
        /** Dernière transmission aux partenaires (dossier le plus récent). */
        public ?string $partnerTransmissionAt,
        /** Rappel par un conseiller : date du consentement en vigueur, null s'il est retiré. */
        public ?string $advisorCallbackAt,
        public bool $advisorCallback,
    ) {}
}
