<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Enum\EmailNotificationStatusEnum;
use App\Enum\EmailNotificationTypeEnum;

final readonly class EmailNotificationDto
{
    public function __construct(
        public EmailNotificationTypeEnum $type,
        public string $label,
        public string $subject,
        public string $recipient,
        public EmailNotificationStatusEnum $status,
        public string $sentAt,
    ) {}
}
