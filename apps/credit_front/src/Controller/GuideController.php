<?php

declare(strict_types=1);

namespace App\Controller;

use App\Seo\JsonLdFactory;
use App\Seo\SeoPage;
use App\Service\ProjectPricingService;
use Credit\Core\Scoring\Calculator\DebtRatioCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GuideController extends AbstractController
{
    /** @var list<array{question: string, answer: string}> */
    private const array FAQ = [
        ['question' => 'Qu\'est-ce que le TAEG ?', 'answer' => 'Le taux annuel effectif global inclut les intérêts et les frais obligatoires. C\'est l\'indicateur légal pour comparer des offres de crédit entre elles.'],
        ['question' => 'Comment est calculé le taux d\'endettement ?', 'answer' => 'Il rapporte vos charges mensuelles de crédit et de logement, nouvelle mensualité comprise, à vos revenus mensuels. Les revenus locatifs sont retenus à 70 %.'],
        ['question' => 'Qu\'est-ce que le reste à vivre ?', 'answer' => 'C\'est ce qui reste chaque mois une fois toutes les charges payées. Il doit couvrir les dépenses courantes du foyer.'],
        ['question' => 'Puis-je changer d\'avis après la signature ?', 'answer' => 'Oui. Vous disposez d\'un délai de rétractation de 14 jours calendaires à compter de la signature de l\'offre de crédit.'],
    ];

    public function __construct(
        private readonly ProjectPricingService $pricing,
        private readonly JsonLdFactory $jsonLd,
    ) {}

    #[Route('/guide/credit-conso', name: 'app_guide_credit_conso', methods: ['GET'])]
    public function creditConso(): Response
    {
        return $this->render('guide/credit-conso.html.twig', [
            'seo' => new SeoPage(
                title: 'Guide du crédit à la consommation : TAEG, endettement, reste à vivre',
                description: 'Comment fonctionne un crédit à la consommation ? TAEG, taux d\'endettement, reste à vivre, délai de rétractation : tout comprendre avant de comparer les offres.',
                canonicalUrl: $this->jsonLd->url('/guide/credit-conso'),
                jsonLd: [
                    $this->jsonLd->breadcrumb([['name' => 'Accueil', 'path' => '/'], ['name' => 'Guide du crédit à la consommation', 'path' => '/guide/credit-conso']]),
                    $this->jsonLd->faq(self::FAQ),
                ],
                ogType: 'article',
            ),
            'faq' => self::FAQ,
            'pricing' => $this->pricing->all(),
            'rentalWeightBps' => DebtRatioCalculator::RENTAL_INCOME_WEIGHT_BPS,
            'singleRemainderCents' => DebtRatioCalculator::SINGLE_MIN_REMAINDER_CENTS,
            'coupleRemainderCents' => DebtRatioCalculator::COUPLE_MIN_REMAINDER_CENTS,
            'childRemainderCents' => DebtRatioCalculator::PER_CHILD_MIN_REMAINDER_CENTS,
        ]);
    }
}
