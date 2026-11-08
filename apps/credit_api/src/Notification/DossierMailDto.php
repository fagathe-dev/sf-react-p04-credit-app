<?php

declare(strict_types=1);

namespace App\Notification;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données des e-mails transactionnels d'un dossier (montants en centimes, taux en points de base).
 */
final readonly class DossierMailDto
{
    /**
     * @param list<string> $partnerNames établissements partenaires du projet
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $recipientEmail,
        public string $recipientName,
        public string $projectLabel,
        public int $amountCents,
        public int $durationMonths,
        public int $quotesCount,
        public ?int $bestMonthlyInstallmentCents,
        public ?int $bestAprBps,
        public array $partnerNames,
        public string $expiresAt,
        /** « 15 000 € » (formaté par FrenchFormatter : aucun calcul décimal dans les gabarits). */
        public string $amountLabel,
        /** « 337,52 € par mois, TAEG fixe de 3,80 % » ou null sans offre. */
        public ?string $bestOfferLabel,
        #[Assert\NotBlank]
        #[Assert\Url(requireTld: false)]
        public string $dossierUrl,
    ) {}
}
