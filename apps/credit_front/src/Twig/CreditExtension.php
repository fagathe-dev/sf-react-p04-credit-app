<?php

declare(strict_types=1);

namespace App\Twig;

use App\Project\ProjectPageRegistry;
use App\Service\TunnelLinkBuilder;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Credit\Core\Util\FrenchFormatter;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

/**
 * Formatage des montants (centimes) et des taux (points de base), liens vers le tunnel, logos et navigation.
 */
final readonly class CreditExtension
{
    public function __construct(
        private TunnelLinkBuilder $tunnelLinkBuilder,
        private ProjectPageRegistry $projectPages,
        private Packages $packages,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** 1500000 → « 15 000 € » ; withCents → « 15 000,00 € ». */
    #[AsTwigFilter('cents')]
    public function cents(int $cents, bool $withCents = false): string
    {
        return FrenchFormatter::cents($cents, $withCents);
    }

    /** 290 → « 2,90 % ». */
    #[AsTwigFilter('bps')]
    public function bps(int $bps, int $decimals = 2): string
    {
        return FrenchFormatter::bps($bps, $decimals);
    }

    #[AsTwigFunction('tunnel_url')]
    public function tunnelUrl(?CreditProjectTypeEnum $project = null, ?int $amountCents = null, ?int $durationMonths = null): string
    {
        return $this->tunnelLinkBuilder->compare($project, $amountCents, $durationMonths);
    }

    /** Simulateur rapide du site (/simulateur), projet présélectionné et UTM conservés. */
    #[AsTwigFunction('simulator_url')]
    public function simulatorUrl(?CreditProjectTypeEnum $project = null): string
    {
        return $this->urlGenerator->generate('app_simulator', array_filter(['project' => $project?->value]) + $this->tunnelLinkBuilder->utmParameters());
    }

    /** Champ « logo » du JSON partenaire (/assets/logos/x.svg) → URL versionnée par AssetMapper. */
    #[AsTwigFunction('partner_logo')]
    public function partnerLogo(string $logoPath): string
    {
        return $this->packages->getUrl(preg_replace('#^/?assets/#', '', $logoPath) ?? $logoPath);
    }

    /** @return list<\App\Project\ProjectPageInterface> */
    #[AsTwigFunction('project_pages')]
    public function projectPages(): array
    {
        return $this->projectPages->all();
    }
}
