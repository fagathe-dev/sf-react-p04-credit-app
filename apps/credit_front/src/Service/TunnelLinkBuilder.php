<?php

declare(strict_types=1);

namespace App\Service;

use Credit\Core\Enum\CreditProjectTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * URL de credit_tunnel avec projet, montant (euros), durée, étape et paramètres UTM reçus par la page.
 *
 * ex. {TUNNEL_URL}/comparer?project=green_loan&amount=15000&duration=60&step=2&utm_source=google
 */
final readonly class TunnelLinkBuilder
{
    public const array UTM_PARAMETERS = ['utm_source', 'utm_medium', 'utm_campaign'];
    public const string COMPARE_PATH = '/comparer';

    public function __construct(
        #[Autowire(env: 'TUNNEL_URL')]
        private string $tunnelUrl,
        private RequestStack $requestStack,
    ) {}

    public function compare(?CreditProjectTypeEnum $project = null, ?int $amountCents = null, ?int $durationMonths = null, int $step = 2): string
    {
        $query = array_filter([
            'project' => $project?->value,
            'amount' => null === $amountCents ? null : intdiv($amountCents, 100),
            'duration' => $durationMonths,
            'step' => null === $project ? 1 : $step,
        ], static fn (mixed $value): bool => null !== $value);

        // « step » est toujours présent : la requête n'est jamais vide
        return $this->compareUrl() . '?' . http_build_query($query + $this->utmParameters());
    }

    /** URL du tunnel sans paramètres : action du formulaire GET du simulateur. */
    public function compareUrl(): string
    {
        return rtrim($this->tunnelUrl, '/') . self::COMPARE_PATH;
    }

    /**
     * Paramètres UTM reçus par la page courante, à recopier vers le tunnel.
     *
     * @return array<string, string>
     */
    public function utmParameters(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $utm = [];

        foreach (self::UTM_PARAMETERS as $parameter) {
            $value = $request?->query->get($parameter);
            if (\is_string($value) && '' !== trim($value)) {
                $utm[$parameter] = mb_substr(trim($value), 0, 255);
            }
        }

        return $utm;
    }
}
