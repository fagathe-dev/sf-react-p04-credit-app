<?php

declare(strict_types=1);

namespace App\Controller;

use App\Project\ProjectPageRegistry;
use App\Seo\JsonLdFactory;
use App\Seo\SeoPage;
use App\Service\ProjectPricingService;
use App\Service\SimulatorDataProvider;
use App\Service\TunnelLinkBuilder;
use Credit\Core\Enum\CreditProjectTypeEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Simulateur rapide : projet, montant, durée et revenus. Mensualité et TAEG indicatifs calculés dans le
 * navigateur (TypeScript) ; le CTA « Comparer les offres personnalisées » est un formulaire GET vers
 * credit_tunnel (étape 2), qui fonctionne aussi sans JavaScript.
 */
final class SimulatorController extends AbstractController
{
    public const array DURATIONS = [12, 24, 36, 48, 60, 72, 84];
    private const int DEFAULT_AMOUNT_EUROS = 10_000;
    private const int DEFAULT_DURATION = 48;

    public function __construct(
        private readonly ProjectPageRegistry $projectPages,
        private readonly ProjectPricingService $pricing,
        private readonly SimulatorDataProvider $simulatorData,
        private readonly TunnelLinkBuilder $tunnelLinkBuilder,
        private readonly JsonLdFactory $jsonLd,
    ) {}

    #[Route('/simulateur', name: 'app_simulator', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $pricing = [];
        foreach ($this->pricing->all() as $item) {
            $pricing[$item->project->value] = $item;
        }

        return $this->render('simulator/index.html.twig', [
            'seo' => new SeoPage(
                title: 'Simulateur de crédit : estimez votre mensualité en quelques secondes',
                description: 'Estimez la mensualité et le TAEG de votre crédit à la consommation selon votre projet, le montant et la durée, puis comparez les offres personnalisées de nos partenaires.',
                canonicalUrl: $this->jsonLd->url('/simulateur'),
                jsonLd: [$this->jsonLd->breadcrumb([['name' => 'Accueil', 'path' => '/'], ['name' => 'Simulateur', 'path' => '/simulateur']])],
            ),
            'pages' => $this->projectPages->all(),
            'pricing' => $pricing,
            'offers' => $this->simulatorData->offers(),
            'durations' => self::DURATIONS,
            'formAction' => $this->tunnelLinkBuilder->compareUrl(),
            'utm' => $this->tunnelLinkBuilder->utmParameters(),
            'selected' => [
                'project' => CreditProjectTypeEnum::tryFrom((string) $request->query->get('project', ''))?->value,
                'amount' => self::positiveInt($request->query->get('amount')) ?? self::DEFAULT_AMOUNT_EUROS,
                'duration' => \in_array(self::positiveInt($request->query->get('duration')), self::DURATIONS, true) ? self::positiveInt($request->query->get('duration')) : self::DEFAULT_DURATION,
                'income' => self::positiveInt($request->query->get('income')),
            ],
        ]);
    }

    private static function positiveInt(mixed $value): ?int
    {
        return \is_string($value) && 1 === preg_match('/^\d{1,7}$/', $value) && (int) $value > 0 ? (int) $value : null;
    }
}
