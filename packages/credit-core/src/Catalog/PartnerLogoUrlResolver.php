<?php

declare(strict_types=1);

namespace Credit\Core\Catalog;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * URL absolue d'un logo partenaire : credit_core.asset_base_uri (DEFAULT_URI de l'application
 * qui sert /assets/logos/) + champ « logo » du JSON.
 */
final readonly class PartnerLogoUrlResolver
{
    public function __construct(
        #[Autowire(param: 'credit_core.asset_base_uri')]
        private ?string $assetBaseUri = null,
    ) {}

    public function resolve(string $logoPath): string
    {
        if (preg_match('#^https?://#i', $logoPath) || null === $this->assetBaseUri || '' === $this->assetBaseUri) {
            return $logoPath;
        }

        return rtrim($this->assetBaseUri, '/') . '/' . ltrim($logoPath, '/');
    }
}
