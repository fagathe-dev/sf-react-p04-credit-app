<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\Partner;

use Credit\Core\Catalog\DTO\PartnerConfigDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Un partenaire du catalogue. Ajouter un partenaire = un fichier JSON + une classe
 * qui étend AbstractJsonPartner : InMemoryProductCatalog n'est pas modifié.
 */
#[AutoconfigureTag(self::TAG)]
interface PartnerInterface
{
    public const string TAG = 'credit_core.partner';

    public function loadConfig(): PartnerConfigDto;
}
