<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

/**
 * Dossier à ajuster : raisons chiffrées du blocage et pistes de rattrapage.
 */
final readonly class AdvisorFallbackDto
{
    /**
     * @param list<string>                  $reasons
     * @param list<AdjustmentSuggestionDto> $suggestions jusqu'à 3, de la moins à la plus éloignée de la demande
     */
    public function __construct(
        public array $reasons,
        public array $suggestions,
        /** Consentement de rappel donné au moment de la demande. */
        public bool $callbackRequested,
        public ?string $phone,
        public string $message,
    ) {}
}
