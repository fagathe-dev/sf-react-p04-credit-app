<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\DTO;

final readonly class PartnerConfigDto
{
    /**
     * @param list<OfferConfigDto> $offers
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $logo,
        public array $offers,
    ) {}
}
