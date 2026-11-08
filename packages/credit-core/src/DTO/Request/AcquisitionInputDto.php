<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Provenance du trafic (paramètres UTM recopiés par credit_front et conservés par le tunnel).
 */
final readonly class AcquisitionInputDto
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $utmSource = null,
        #[Assert\Length(max: 255)]
        public ?string $utmMedium = null,
        #[Assert\Length(max: 255)]
        public ?string $utmCampaign = null,
        #[Assert\Length(max: 1024)]
        public ?string $referrer = null,
    ) {}

    public function isEmpty(): bool
    {
        return null === $this->utmSource && null === $this->utmMedium && null === $this->utmCampaign && null === $this->referrer;
    }
}
