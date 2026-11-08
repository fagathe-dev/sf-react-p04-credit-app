<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\Partner;

use Credit\Core\Catalog\DTO\OfferConfigDto;
use Credit\Core\Catalog\DTO\OfferCriteriaDto;
use Credit\Core\Catalog\DTO\PartnerConfigDto;
use Credit\Core\Catalog\Exception\InvalidCatalogException;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Enum\EmploymentStatusEnum;
use Credit\Core\Enum\RiskGradeEnum;
use Credit\Core\Util\UnitConverter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Charge, valide et met en cache mémoire le JSON d'un partenaire (config/data/<code>.json).
 *
 * Le JSON est écrit en unités humaines (euros, pourcentages) ; tout est converti
 * en centimes et en points de base dès la lecture.
 */
abstract class AbstractJsonPartner implements PartnerInterface
{
    private ?PartnerConfigDto $cachedConfig = null;

    public function __construct(
        #[Autowire(param: 'credit_core.data_dir')]
        private readonly string $dataDir,
    ) {}

    /** Nom du fichier JSON dans le dossier de données, ex. credit_nova.json. */
    abstract protected function getJsonFile(): string;

    public function loadConfig(): PartnerConfigDto
    {
        if (null !== $this->cachedConfig) {
            return $this->cachedConfig;
        }

        $path = rtrim($this->dataDir, '/\\') . '/' . $this->getJsonFile();
        $content = is_file($path) ? file_get_contents($path) : false;

        if (false === $content) {
            throw new InvalidCatalogException(\sprintf('Fichier partenaire introuvable : %s', $path));
        }

        try {
            /** @var array<string, mixed> $raw */
            $raw = json_decode($content, true, 16, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidCatalogException(\sprintf('JSON invalide dans %s : %s', $path, $e->getMessage()), previous: $e);
        }

        foreach (['code', 'name', 'logo', 'offers'] as $key) {
            if (!\array_key_exists($key, $raw)) {
                throw new InvalidCatalogException(\sprintf('Clé "%s" manquante dans %s.', $key, $path));
            }
        }

        $code = (string) $raw['code'];
        $name = (string) $raw['name'];
        $logo = (string) $raw['logo'];

        $offers = [];
        foreach ((array) $raw['offers'] as $rawOffer) {
            $offers[] = $this->buildOffer((array) $rawOffer, $code, $name, $logo, $path);
        }

        return $this->cachedConfig = new PartnerConfigDto(
            code: $code,
            name: $name,
            logo: $logo,
            offers: $offers,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function buildOffer(array $raw, string $partnerCode, string $partnerName, string $partnerLogo, string $path): OfferConfigDto
    {
        try {
            $criteria = (array) $raw['criteria'];

            $offer = new OfferConfigDto(
                partnerCode: $partnerCode,
                partnerName: $partnerName,
                partnerLogo: $partnerLogo,
                offerCode: (string) $raw['offerCode'],
                productName: (string) $raw['productName'],
                projectType: CreditProjectTypeEnum::from((string) $raw['projectType']),
                nominalRateBps: UnitConverter::percentToBps($raw['nominalRate']),
                aprBps: UnitConverter::percentToBps($raw['apr']),
                insuranceRateBps: UnitConverter::percentToBps($raw['insuranceRate'] ?? 0),
                referenceAmountCents: UnitConverter::eurosToCents($raw['referenceAmount']),
                referenceDurationMonths: (int) $raw['referenceDurationMonths'],
                referenceMonthlyInstallmentCents: UnitConverter::eurosToCents($raw['monthlyInstallment']),
                referenceTotalFinancingCostCents: UnitConverter::eurosToCents($raw['totalFinancingCost']),
                criteria: new OfferCriteriaDto(
                    minAmountCents: UnitConverter::eurosToCents($criteria['minAmount']),
                    maxAmountCents: UnitConverter::eurosToCents($criteria['maxAmount']),
                    minDurationMonths: (int) $criteria['minDurationMonths'],
                    maxDurationMonths: (int) $criteria['maxDurationMonths'],
                    minMonthlyIncomeCents: UnitConverter::eurosToCents($criteria['minMonthlyIncome']),
                    maxDebtRatioBps: UnitConverter::percentToBps($criteria['maxDebtRatioPercent']),
                    minRemainderToLiveCents: UnitConverter::eurosToCents($criteria['minRemainderToLive'] ?? 0),
                    minSeniorityMonths: (int) ($criteria['minSeniorityMonths'] ?? 0),
                    minRiskGrade: RiskGradeEnum::from((string) ($criteria['minRiskGrade'] ?? RiskGradeEnum::GRADE_E->value)),
                    allowedEmploymentStatuses: array_values(array_map(
                        static fn (mixed $status): EmploymentStatusEnum => EmploymentStatusEnum::from((string) $status),
                        (array) $criteria['allowedEmploymentStatuses'],
                    )),
                ),
            );
        } catch (\Throwable $e) {
            throw new InvalidCatalogException(\sprintf('Offre invalide dans %s : %s', $path, $e->getMessage()), previous: $e);
        }

        $c = $offer->criteria;
        if ($c->minAmountCents > $c->maxAmountCents || $c->minDurationMonths > $c->maxDurationMonths || $c->minDurationMonths < 1) {
            throw new InvalidCatalogException(\sprintf('Bornes incohérentes pour l\'offre %s dans %s.', $offer->offerCode, $path));
        }

        return $offer;
    }
}
