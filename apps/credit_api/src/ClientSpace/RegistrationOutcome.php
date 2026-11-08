<?php

declare(strict_types=1);

namespace App\ClientSpace;

use App\Entity\Client;
use App\Entity\Dossier;
use Credit\Core\Enum\ClientStatusEnum;

final readonly class RegistrationOutcome
{
    public function __construct(
        public ClientStatusEnum $clientStatus,
        public Client $client,
        public Dossier $dossier,
    ) {}

    /** Une session doit être ouverte (nouveau client). */
    public function opensSession(): bool
    {
        return ClientStatusEnum::CREATED === $this->clientStatus;
    }
}
