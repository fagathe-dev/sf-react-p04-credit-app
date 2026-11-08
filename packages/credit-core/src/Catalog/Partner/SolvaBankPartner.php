<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\Partner;

final class SolvaBankPartner extends AbstractJsonPartner
{
    protected function getJsonFile(): string
    {
        return 'solva_bank.json';
    }
}
