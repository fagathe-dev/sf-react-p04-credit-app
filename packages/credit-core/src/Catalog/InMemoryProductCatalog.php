<?php

declare(strict_types=1);

namespace Credit\Core\Catalog;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Catalog\DTO\PartnerConfigDto;
use Credit\Core\Catalog\Partner\PartnerInterface;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Agrège les partenaires (5 partenaires, 20 produits) et garde le catalogue en mémoire.
 */
final class InMemoryProductCatalog
{
    /** @var list<PartnerConfigDto>|null */
    private ?array $partners = null;

    /**
     * @param iterable<PartnerInterface> $partnerSources
     */
    public function __construct(
        #[AutowireIterator(PartnerInterface::TAG)]
        private readonly iterable $partnerSources,
    ) {}

    /**
     * @return list<PartnerConfigDto>
     */
    public function getPartners(): array
    {
        if (null === $this->partners) {
            $partners = [];
            foreach ($this->partnerSources as $source) {
                $partners[] = $source->loadConfig();
            }
            usort($partners, static fn (PartnerConfigDto $a, PartnerConfigDto $b): int => strcmp($a->name, $b->name));
            $this->partners = $partners;
        }

        return $this->partners;
    }

    /**
     * @return list<OfferConfigDto>
     */
    public function getOffers(): array
    {
        $offers = [];
        foreach ($this->getPartners() as $partner) {
            array_push($offers, ...$partner->offers);
        }

        return $offers;
    }

    /**
     * @return list<OfferConfigDto>
     */
    public function getOffersByProject(CreditProjectTypeEnum $projectType): array
    {
        return array_values(array_filter(
            $this->getOffers(),
            static fn (OfferConfigDto $offer): bool => $offer->projectType === $projectType,
        ));
    }

    public function findOffer(string $offerCode): ?OfferConfigDto
    {
        foreach ($this->getOffers() as $offer) {
            if ($offer->offerCode === $offerCode) {
                return $offer;
            }
        }

        return null;
    }

    /**
     * Offre au plus petit TAEG d'un projet (barème « à partir de »).
     */
    public function findLowestAprOffer(CreditProjectTypeEnum $projectType): ?OfferConfigDto
    {
        $offers = $this->getOffersByProject($projectType);
        usort($offers, static fn (OfferConfigDto $a, OfferConfigDto $b): int => [$a->aprBps, $a->offerCode] <=> [$b->aprBps, $b->offerCode]);

        return $offers[0] ?? null;
    }
}
