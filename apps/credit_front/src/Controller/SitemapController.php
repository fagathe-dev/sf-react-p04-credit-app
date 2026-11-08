<?php

declare(strict_types=1);

namespace App\Controller;

use App\Project\ProjectPageRegistry;
use App\Seo\JsonLdFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Fichiers d'indexation : sitemap.xml (accueil, pages projet, guide) et robots.txt.
 */
final class SitemapController extends AbstractController
{
    public function __construct(
        private readonly ProjectPageRegistry $projectPages,
        private readonly JsonLdFactory $jsonLd,
    ) {}

    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'], format: 'xml')]
    public function sitemap(): Response
    {
        $urls = [['loc' => $this->jsonLd->url('/'), 'priority' => '1.0']];
        foreach ($this->projectPages->all() as $page) {
            $urls[] = ['loc' => $this->jsonLd->url('/' . $page->getSlug()), 'priority' => '0.9'];
        }
        $urls[] = ['loc' => $this->jsonLd->url('/simulateur'), 'priority' => '0.8'];
        $urls[] = ['loc' => $this->jsonLd->url('/guide/credit-conso'), 'priority' => '0.7'];

        $response = $this->render('sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response->setPublic()->setMaxAge(3600);
    }

    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'], format: 'txt')]
    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /mentions-legales',
            'Sitemap: ' . $this->jsonLd->url('/sitemap.xml'),
            '',
        ]);

        return (new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']))->setPublic()->setMaxAge(3600);
    }
}
