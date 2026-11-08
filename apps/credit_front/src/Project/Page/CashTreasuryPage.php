<?php

declare(strict_types=1);

namespace App\Project\Page;

use App\Project\ProjectPageInterface;
use Credit\Core\Enum\CreditProjectTypeEnum;

final class CashTreasuryPage implements ProjectPageInterface
{
    public function getSlug(): string
    {
        return 'tresorerie-express';
    }

    public function getProjectType(): CreditProjectTypeEnum
    {
        return CreditProjectTypeEnum::CASH_TREASURY;
    }

    public function getPosition(): int
    {
        return 50;
    }

    public function getMetaTitle(): string
    {
        return 'Trésorerie express : comparez les prêts personnels non affectés';
    }

    public function getMetaDescription(): string
    {
        return 'Besoin de trésorerie pour un imprévu ou un projet personnel ? Comparez les prêts non affectés de nos partenaires, avec des mensualités fixes et un coût total affiché.';
    }

    public function getHeading(): string
    {
        return 'Trésorerie express : un prêt personnel libre d\'usage';
    }

    public function getTagline(): string
    {
        return 'Imprévu, événement familial, projet libre.';
    }

    public function getIntroduction(): array
    {
        return [
            'Le prêt de trésorerie est un prêt personnel non affecté : vous n\'avez pas à justifier l\'usage des fonds. Les mensualités et le taux restent fixes pendant toute la durée.',
            'Avant de vous engager, vérifiez que la mensualité laisse un reste à vivre suffisant : le comparateur le calcule pour vous.',
        ];
    }

    public function getUseCases(): array
    {
        return ['Dépense imprévue', 'Mariage ou événement familial', 'Voyage', 'Études ou formation', 'Projet personnel'];
    }

    public function getFaq(): array
    {
        return [
            ['question' => 'Puis-je regrouper mes crédits avec ce prêt ?', 'answer' => 'Non : le rachat de crédits est une opération différente. Ce prêt s\'ajoute à vos charges existantes, prises en compte dans le calcul de votre taux d\'endettement.'],
            ['question' => 'Pourquoi le taux est-il plus élevé ?', 'answer' => 'Un prêt non affecté présente plus de risques pour le prêteur qu\'un prêt lié à un achat précis, d\'où un taux généralement plus élevé.'],
            ['question' => 'Quel délai de rétractation ?', 'answer' => 'Vous disposez de 14 jours calendaires après la signature de l\'offre de crédit pour vous rétracter.'],
        ];
    }

    public function getDefaultAmountCents(): int
    {
        return 500_000;
    }

    public function getDefaultDurationMonths(): int
    {
        return 36;
    }
}
