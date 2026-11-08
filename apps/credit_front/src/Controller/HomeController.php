<?php

declare(strict_types=1);

namespace App\Controller;

use App\Project\ProjectPageRegistry;
use App\Seo\JsonLdFactory;
use App\Seo\SeoPage;
use App\Service\ProjectPricingService;
use Credit\Core\Catalog\InMemoryProductCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly ProjectPageRegistry $projectPages,
        private readonly ProjectPricingService $pricing,
        private readonly InMemoryProductCatalog $catalog,
        private readonly JsonLdFactory $jsonLd,
    ) {}

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function __invoke(): Response
    {
        $pricingByProject = [];
        foreach ($this->pricing->all() as $pricing) {
            $pricingByProject[$pricing->project->value] = $pricing;
        }

        return $this->render('home.html.twig', [
            'seo' => new SeoPage(
                title: 'Comparateur de crédit à la consommation : offres personnalisées en quelques minutes',
                description: 'Comparez les crédits à la consommation de 5 établissements partenaires : crédit vert, prêt auto, prêt travaux, prêt personnel et trésorerie. Mensualité, TAEG et coût total pour votre profil.',
                canonicalUrl: $this->jsonLd->url('/'),
                jsonLd: [$this->jsonLd->organization(), $this->jsonLd->website()],
            ),
            'pages' => $this->projectPages->all(),
            'pricing' => $pricingByProject,
            'partners' => $this->catalog->getPartners(),
        ]);
    }
}
