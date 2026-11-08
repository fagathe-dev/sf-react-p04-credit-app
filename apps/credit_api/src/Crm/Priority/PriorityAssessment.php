<?php

declare(strict_types=1);

namespace App\Crm\Priority;

use App\Enum\CallbackPriorityEnum;

final readonly class PriorityAssessment
{
    public function __construct(
        public CallbackPriorityEnum $priority,
        public string $reason,
    ) {}
}
