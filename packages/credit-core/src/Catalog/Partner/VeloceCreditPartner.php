<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\Partner;

final class VeloceCreditPartner extends AbstractJsonPartner
{
    protected function getJsonFile(): string
    {
        return 'veloce_credit.json';
    }
}
