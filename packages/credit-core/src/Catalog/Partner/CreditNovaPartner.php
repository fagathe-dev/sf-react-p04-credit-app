<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\Partner;

final class CreditNovaPartner extends AbstractJsonPartner
{
    protected function getJsonFile(): string
    {
        return 'credit_nova.json';
    }
}
