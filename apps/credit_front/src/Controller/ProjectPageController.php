<?php

declare(strict_types=1);

namespace App\Controller;

use App\Project\ProjectPageRegistry;
use App\Seo\JsonLdFactory;
use App\Seo\SeoPage;
use App\Service\ProjectPricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Une page par projet (/credit-vert, /pret-auto…), découverte dans ProjectPageRegistry.
 * Route de priorité basse : les routes explicites du site passent avant.
 */
final class ProjectPageController extends AbstractController
{
    public function __construct(
        private readonly ProjectPageRegistry $projectPages,
        private readonly ProjectPricingService $pricing,
        private readonly JsonLdFactory $jsonLd,
    ) {}

    #[Route('/{slug}', name: 'app_project_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -10)]
    public function __invoke(string $slug): Response
    {
        $page = $this->projectPages->findBySlug($slug) ?? throw $this->createNotFoundException();
        $pricing = $this->pricing->forProject($page->getProjectType());

        $jsonLd = [
            $this->jsonLd->breadcrumb([
                ['name' => 'Accueil', 'path' => '/'],
                ['name' => $page->getProjectType()->label(), 'path' => '/' . $page->getSlug()],
            ]),
            $this->jsonLd->faq($page->getFaq()),
        ];
        if (null !== $pricing) {
            $jsonLd[] = $this->jsonLd->loanOrCredit($page, $pricing);
        }

        return $this->render('project/show.html.twig', [
            'seo' => new SeoPage(
                title: $page->getMetaTitle(),
                description: $page->getMetaDescription(),
                canonicalUrl: $this->jsonLd->url('/' . $page->getSlug()),
                jsonLd: $jsonLd,
            ),
            'page' => $page,
            'pricing' => $pricing,
        ]);
    }
}
