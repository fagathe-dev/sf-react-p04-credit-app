<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\Partner;

final class FlashPactPartner extends AbstractJsonPartner
{
    protected function getJsonFile(): string
    {
        return 'flash_pact.json';
    }
}
