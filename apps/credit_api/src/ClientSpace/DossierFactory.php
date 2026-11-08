<?php

declare(strict_types=1);

namespace App\ClientSpace;

use App\Entity\Client;
use App\Entity\Dossier;
use Credit\Core\DTO\Request\ComparisonRequestDto;
use Credit\Core\DTO\Response\ComparisonResultDto;
use Credit\Core\Enum\DossierStatusEnum;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Construit le client et le dossier à partir de la demande et du résultat du moteur (sans les persister).
 * Partagé par ClientRegistrationService (fin de tunnel) et les fixtures : les données de test sont
 * produites exactement comme en production.
 */
final readonly class DossierFactory
{
    /**
     * Champs exclus du récapitulatif : consentements et acquisition (colonnes dédiées),
     * getter de validation du DTO.
     */
    private const array EXCLUDED_FIELDS = ['consentPartnerTransmission', 'consentAdvisorCallback', 'consentClientSpaceCreation', 'acquisition', 'coBorrowerConsistent'];

    public function __construct(
        private NormalizerInterface $normalizer,
    ) {}

    public function createClient(ComparisonRequestDto $request, ?\DateTimeImmutable $at = null): Client
    {
        $birthDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $request->birthDate);

        return (new Client($request->normalizedEmail()))
            ->setCivility($request->civility)
            ->setFirstName(trim($request->firstName))
            ->setLastName(trim($request->lastName))
            ->setBirthDate(false === $birthDate ? new \DateTimeImmutable('today') : $birthDate)
            ->setPhone($request->phone)
            ->setConsentClientSpaceCreatedAt($at ?? new \DateTimeImmutable());
    }

    public function createDossier(Client $client, ComparisonRequestDto $request, ComparisonResultDto $result, DossierStatusEnum $status): Dossier
    {
        /** @var array<string, mixed> $snapshot */
        $snapshot = $this->normalizer->normalize($request);
        foreach (self::EXCLUDED_FIELDS as $field) {
            unset($snapshot[$field]);
        }

        /** @var array<string, mixed> $summary */
        $summary = $this->normalizer->normalize($result->scoringSummary);
        /** @var list<array<string, mixed>> $quotes */
        $quotes = $this->normalizer->normalize($result->quotes);
        /** @var array<string, mixed>|null $fallback */
        $fallback = null === $result->advisorFallback ? null : $this->normalizer->normalize($result->advisorFallback);
        /** @var array<string, mixed>|null $rejection */
        $rejection = null === $result->rejection ? null : $this->normalizer->normalize($result->rejection);

        $dossier = (new Dossier($client, $status, $result->decision))
            ->setRequestSnapshot($snapshot)
            ->setScoringSummary($summary)
            ->setQuotes($quotes)
            ->setAdvisorFallback($fallback)
            ->setRejection($rejection)
            ->setConsentAdvisorCallback($request->consentAdvisorCallback);

        if (null !== $request->acquisition && !$request->acquisition->isEmpty()) {
            $dossier->setAcquisition([
                'utm_source' => $request->acquisition->utmSource,
                'utm_medium' => $request->acquisition->utmMedium,
                'utm_campaign' => $request->acquisition->utmCampaign,
                'referrer' => $request->acquisition->referrer,
            ]);
        }

        return $dossier;
    }
}
