<?php

declare(strict_types=1);

namespace App\Project\Page;

use App\Project\ProjectPageInterface;
use Credit\Core\Enum\CreditProjectTypeEnum;

final class HomeImprovementPage implements ProjectPageInterface
{
    public function getSlug(): string
    {
        return 'pret-travaux';
    }

    public function getProjectType(): CreditProjectTypeEnum
    {
        return CreditProjectTypeEnum::HOME_IMPROVEMENT;
    }

    public function getPosition(): int
    {
        return 30;
    }

    public function getMetaTitle(): string
    {
        return 'Prêt travaux : comparez les crédits pour rénover votre logement';
    }

    public function getMetaDescription(): string
    {
        return 'Cuisine, salle de bain, extension ou rénovation : comparez les prêts travaux de nos partenaires jusqu\'à 96 mois et trouvez la mensualité adaptée à votre budget.';
    }

    public function getHeading(): string
    {
        return 'Prêt travaux : rénovez votre logement à votre rythme';
    }

    public function getTagline(): string
    {
        return 'Cuisine, salle de bain, rénovation, extension.';
    }

    public function getIntroduction(): array
    {
        return [
            'Le prêt travaux finance l\'amélioration de votre résidence principale ou secondaire, que vous en soyez propriétaire ou locataire avec l\'accord du bailleur.',
            'Les durées sont plus longues que pour les autres crédits à la consommation, ce qui permet d\'étaler le remboursement de projets importants.',
        ];
    }

    public function getUseCases(): array
    {
        return ['Cuisine ou salle de bain', 'Rénovation complète', 'Extension ou véranda', 'Toiture et façade', 'Aménagement des combles'];
    }

    public function getFaq(): array
    {
        return [
            ['question' => 'Quel montant puis-je emprunter ?', 'answer' => 'Selon les partenaires, de 2 500 € à 75 000 €. Le montant accordé dépend de vos revenus, de vos charges et de votre reste à vivre.'],
            ['question' => 'Faut-il être propriétaire ?', 'answer' => 'Non. Un locataire peut financer des travaux d\'aménagement avec l\'accord de son propriétaire.'],
            ['question' => 'Les travaux de rénovation énergétique sont-ils concernés ?', 'answer' => 'Oui, mais le crédit vert propose souvent un taux plus bas pour ces projets. Comparez les deux.'],
        ];
    }

    public function getDefaultAmountCents(): int
    {
        return 2_000_000;
    }

    public function getDefaultDurationMonths(): int
    {
        return 72;
    }
}
