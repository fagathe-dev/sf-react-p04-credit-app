<?php

declare(strict_types=1);

namespace App\Notification;

use App\Entity\Dossier;
use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Catalog\InMemoryProductCatalog;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Util\FrenchFormatter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Construit le DossierMailDto d'un dossier à partir de son résultat figé.
 */
final readonly class DossierMailFactory
{
    public function __construct(
        private InMemoryProductCatalog $catalog,
        #[Autowire(env: 'FRONTEND_URL')]
        private string $frontendUrl,
    ) {}

    public function build(Dossier $dossier): DossierMailDto
    {
        $request = $dossier->getRequestSnapshot();
        $project = CreditProjectTypeEnum::tryFrom((string) ($request['projectType'] ?? '')) ?? CreditProjectTypeEnum::PERSONAL_GOODS;
        $quotes = $dossier->getQuotes();
        $best = $quotes[0] ?? null;
        $client = $dossier->getClient();

        return new DossierMailDto(
            recipientEmail: $client->getEmail(),
            recipientName: $client->getFullName(),
            projectLabel: $project->label(),
            amountCents: (int) ($request['requestedAmountCents'] ?? 0),
            durationMonths: (int) ($request['durationMonths'] ?? 0),
            quotesCount: \count($quotes),
            bestMonthlyInstallmentCents: null === $best ? null : (int) $best['monthlyInstallmentCents'],
            bestAprBps: null === $best ? null : (int) $best['aprBps'],
            partnerNames: array_values(array_unique(array_map(static fn (OfferConfigDto $offer): string => $offer->partnerName, $this->catalog->getOffersByProject($project)))),
            expiresAt: $dossier->getQuotesExpireAt()->format('d/m/Y'),
            amountLabel: FrenchFormatter::cents((int) ($request['requestedAmountCents'] ?? 0), false),
            bestOfferLabel: null === $best ? null : \sprintf(
                '%s par mois, TAEG fixe de %s',
                FrenchFormatter::cents((int) $best['monthlyInstallmentCents']),
                FrenchFormatter::bps((int) $best['aprBps'], 2),
            ),
            dossierUrl: rtrim($this->frontendUrl, '/') . '/dossiers/' . $dossier->getId()->toRfc4122(),
        );
    }
}
