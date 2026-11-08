<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\Partner;

final class AltisFinancePartner extends AbstractJsonPartner
{
    protected function getJsonFile(): string
    {
        return 'altis_finance.json';
    }
}
