<?php

declare(strict_types=1);

namespace App\Enum;

enum EmailNotificationStatusEnum: string
{
    case SENT = 'SENT';
    case FAILED = 'FAILED';
}
