<?php

declare(strict_types=1);

namespace App\Seo;

use App\Project\ProjectPageInterface;
use App\Service\ProjectPricing;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Données structurées schema.org (JSON-LD) des pages du site vitrine.
 */
final readonly class JsonLdFactory
{
    public function __construct(
        #[Autowire(env: 'DEFAULT_URI')]
        private string $siteUrl,
        #[Autowire(env: 'APP_NAME')]
        private string $appName,
    ) {}

    public function url(string $path = '/'): string
    {
        return rtrim($this->siteUrl, '/') . '/' . ltrim($path, '/');
    }

    /** @return array<string, mixed> */
    public function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->appName,
            'url' => $this->url('/'),
        ];
    }

    /** @return array<string, mixed> */
    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->appName,
            'url' => $this->url('/'),
            'inLanguage' => 'fr-FR',
        ];
    }

    /**
     * @param list<array{name: string, path: string}> $items
     *
     * @return array<string, mixed>
     */
    public function breadcrumb(array $items): array
    {
        $elements = [];
        foreach ($items as $index => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $this->url($item['path']),
            ];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }

    /**
     * @param list<array{question: string, answer: string}> $faq
     *
     * @return array<string, mixed>
     */
    public function faq(array $faq): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn(array $entry): array => [
                '@type' => 'Question',
                'name' => $entry['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $entry['answer']],
            ], $faq),
        ];
    }

    /**
     * Produit financier d'une page projet : TAEG minimal et bornes lus dans le catalogue.
     *
     * @return array<string, mixed>
     */
    public function loanOrCredit(ProjectPageInterface $page, ProjectPricing $pricing): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'LoanOrCredit',
            'name' => $page->getProjectType()->label(),
            'description' => $page->getMetaDescription(),
            'url' => $this->url('/' . $page->getSlug()),
            'provider' => ['@type' => 'Organization', 'name' => $this->appName],
            'currency' => 'EUR',
            'annualPercentageRate' => [
                '@type' => 'QuantitativeValue',
                'minValue' => self::bpsToDecimalString($pricing->fromAprBps),
                'unitText' => 'PERCENT',
            ],
            'amount' => [
                '@type' => 'MonetaryAmount',
                'currency' => 'EUR',
                'minValue' => intdiv($pricing->minAmountCents, 100),
                'maxValue' => intdiv($pricing->maxAmountCents, 100),
            ],
            'loanTerm' => [
                '@type' => 'QuantitativeValue',
                'minValue' => $pricing->minDurationMonths,
                'maxValue' => $pricing->maxDurationMonths,
                'unitCode' => 'MON',
            ],
            'areaServed' => ['@type' => 'Country', 'name' => 'France métropolitaine et Corse'],
        ];
    }

    /** 290 → « 2.90 » (chaîne : aucun float). */
    private static function bpsToDecimalString(int $bps): string
    {
        return intdiv($bps, 100) . '.' . str_pad((string) ($bps % 100), 2, '0', STR_PAD_LEFT);
    }
}
