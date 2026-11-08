<?php

declare(strict_types=1);

namespace App\Mailer;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OtpCodeMailDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $recipientEmail,
        public string $recipientName,
        #[Assert\Regex('/^\d{6}$/')]
        public string $code,
        #[Assert\Positive]
        public int $ttlMinutes,
        #[Assert\NotBlank]
        #[Assert\Url(requireTld: false)]
        public string $loginUrl,
    ) {}
}
