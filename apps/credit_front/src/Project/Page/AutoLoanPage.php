<?php

declare(strict_types=1);

namespace App\Project\Page;

use App\Project\ProjectPageInterface;
use Credit\Core\Enum\CreditProjectTypeEnum;

final class AutoLoanPage implements ProjectPageInterface
{
    public function getSlug(): string
    {
        return 'pret-auto';
    }

    public function getProjectType(): CreditProjectTypeEnum
    {
        return CreditProjectTypeEnum::AUTO;
    }

    public function getPosition(): int
    {
        return 20;
    }

    public function getMetaTitle(): string
    {
        return 'Prêt auto : comparez les crédits voiture neuve ou d\'occasion';
    }

    public function getMetaDescription(): string
    {
        return 'Voiture neuve, occasion ou deux-roues : comparez les prêts auto de nos partenaires, avec mensualité, TAEG et coût total affichés pour votre profil.';
    }

    public function getHeading(): string
    {
        return 'Prêt auto : financez votre véhicule au meilleur taux';
    }

    public function getTagline(): string
    {
        return 'Voiture neuve ou d\'occasion, moto, utilitaire.';
    }

    public function getIntroduction(): array
    {
        return [
            'Le prêt auto est un crédit affecté à l\'achat d\'un véhicule. Si la vente n\'a pas lieu, le crédit est annulé : vous êtes protégé.',
            'Comparer les offres permet de choisir entre plusieurs taux et plusieurs durées, et de vérifier que la mensualité reste compatible avec votre budget.',
        ];
    }

    public function getUseCases(): array
    {
        return ['Voiture neuve', 'Voiture d\'occasion', 'Moto ou scooter', 'Camping-car', 'Véhicule utilitaire'];
    }

    public function getFaq(): array
    {
        return [
            ['question' => 'Prêt auto ou prêt personnel ?', 'answer' => 'Le prêt auto est lié à l\'achat du véhicule et vous protège si la vente est annulée. Le prêt personnel laisse l\'usage des fonds libre, avec un taux souvent un peu plus élevé.'],
            ['question' => 'Puis-je financer une voiture d\'occasion ?', 'answer' => 'Oui, plusieurs de nos partenaires financent les véhicules d\'occasion, chez un professionnel ou entre particuliers.'],
            ['question' => 'Puis-je rembourser par anticipation ?', 'answer' => 'Oui. Le remboursement anticipé est toujours possible ; des indemnités limitées par la loi peuvent s\'appliquer.'],
        ];
    }

    public function getDefaultAmountCents(): int
    {
        return 1_500_000;
    }

    public function getDefaultDurationMonths(): int
    {
        return 48;
    }
}
