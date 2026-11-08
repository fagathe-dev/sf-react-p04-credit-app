<?php

declare(strict_types=1);

namespace App\Project\Page;

use App\Project\ProjectPageInterface;
use Credit\Core\Enum\CreditProjectTypeEnum;

final class GreenLoanPage implements ProjectPageInterface
{
    public function getSlug(): string
    {
        return 'credit-vert';
    }

    public function getProjectType(): CreditProjectTypeEnum
    {
        return CreditProjectTypeEnum::GREEN_LOAN;
    }

    public function getPosition(): int
    {
        return 10;
    }

    public function getMetaTitle(): string
    {
        return 'Crédit vert : comparez les prêts rénovation énergétique et mobilité propre';
    }

    public function getMetaDescription(): string
    {
        return 'Panneaux solaires, pompe à chaleur, véhicule électrique : comparez les crédits verts de nos partenaires et obtenez vos offres personnalisées en quelques minutes.';
    }

    public function getHeading(): string
    {
        return 'Crédit vert : financez la transition énergétique de votre foyer';
    }

    public function getTagline(): string
    {
        return 'Rénovation énergétique, solaire, véhicule électrique.';
    }

    public function getIntroduction(): array
    {
        return [
            'Le crédit vert est un prêt personnel affecté aux projets qui réduisent la consommation d\'énergie ou les émissions de votre foyer. Ses taux sont généralement parmi les plus bas du crédit à la consommation.',
            'Le montant est versé pour un projet précis, souvent sur présentation d\'un devis. Vous remboursez ensuite des mensualités fixes, connues dès la signature.',
        ];
    }

    public function getUseCases(): array
    {
        return ['Panneaux photovoltaïques', 'Pompe à chaleur', 'Isolation des combles et des murs', 'Véhicule électrique ou hybride rechargeable', 'Vélo à assistance électrique'];
    }

    public function getFaq(): array
    {
        return [
            ['question' => 'Le crédit vert est-il cumulable avec les aides publiques ?', 'answer' => 'Oui. Le crédit vert finance le reste à charge après déduction des aides auxquelles vous avez droit. Il ne remplace pas ces aides.'],
            ['question' => 'Faut-il fournir un devis ?', 'answer' => 'Certains partenaires demandent un devis ou une facture pour verser les fonds, car le prêt est affecté à votre projet.'],
            ['question' => 'Quelle durée choisir ?', 'answer' => 'Une durée plus longue réduit la mensualité mais augmente le coût total. Le comparateur vous montre les deux pour chaque offre.'],
        ];
    }

    public function getDefaultAmountCents(): int
    {
        return 1_500_000;
    }

    public function getDefaultDurationMonths(): int
    {
        return 60;
    }
}
