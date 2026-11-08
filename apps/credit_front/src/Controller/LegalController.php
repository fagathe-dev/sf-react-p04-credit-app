<?php

declare(strict_types=1);

namespace App\Controller;

use App\Seo\JsonLdFactory;
use App\Seo\SeoPage;
use Credit\Core\Catalog\InMemoryProductCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages légales statiques (contenu en dur dans templates/legal/, aucune base de données).
 * Liées depuis le pied de page du site et depuis le bandeau du tunnel (credit_tunnel, nouvel onglet).
 * Seuls les noms des partenaires et le nombre de produits viennent du catalogue JSON de credit-core.
 */
final class LegalController extends AbstractController
{
    public function __construct(
        private readonly JsonLdFactory $jsonLd,
        private readonly InMemoryProductCatalog $catalog,
    ) {}

    #[Route('/mentions-legales', name: 'app_legal_mentions', methods: ['GET'])]
    public function mentions(): Response
    {
        return $this->renderLegalPage(
            'legal/mentions.html.twig',
            '/mentions-legales',
            'Mentions légales',
            'Mentions légales du comparateur de crédit : éditeur, hébergement et statut d\'intermédiaire (projet de démonstration).',
        );
    }

    #[Route('/gestion-donnees-personnelles', name: 'app_legal_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->renderLegalPage(
            'legal/privacy.html.twig',
            '/gestion-donnees-personnelles',
            'Politique de confidentialité et gestion des données',
            'Finalités, transmission aux partenaires, sécurité et droits RGPD sur les données saisies dans le comparateur de crédit.',
        );
    }

    #[Route('/mentions-comparateurs', name: 'app_legal_comparator', methods: ['GET'])]
    public function comparator(): Response
    {
        return $this->renderLegalPage(
            'legal/comparator.html.twig',
            '/mentions-comparateurs',
            'Fonctionnement de notre comparateur',
            'Critère de classement des offres, périmètre du panel de partenaires et rémunération du comparateur de crédit.',
        );
    }

    #[Route('/cgu', name: 'app_legal_cgu', methods: ['GET'])]
    public function cgu(): Response
    {
        return $this->renderLegalPage(
            'legal/cgu.html.twig',
            '/cgu',
            'Conditions Générales d\'Utilisation',
            'Conditions générales d\'utilisation du simulateur et du comparateur de crédit à la consommation.',
        );
    }

    private function renderLegalPage(string $template, string $path, string $title, string $description): Response
    {
        return $this->render($template, [
            'seo' => new SeoPage(
                title: $title,
                description: $description,
                canonicalUrl: $this->jsonLd->url($path),
                robots: 'noindex, follow',
            ),
            'partners' => $this->catalog->getPartners(),
            'offerCount' => \count($this->catalog->getOffers()),
        ]);
    }
}
