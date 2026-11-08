<?php

declare(strict_types=1);

namespace App\Project\Page;

use App\Project\ProjectPageInterface;
use Credit\Core\Enum\CreditProjectTypeEnum;

final class PersonalLoanPage implements ProjectPageInterface
{
    public function getSlug(): string
    {
        return 'pret-personnel';
    }

    public function getProjectType(): CreditProjectTypeEnum
    {
        return CreditProjectTypeEnum::PERSONAL_GOODS;
    }

    public function getPosition(): int
    {
        return 40;
    }

    public function getMetaTitle(): string
    {
        return 'Prêt personnel : comparez les crédits équipement, mobilier et high-tech';
    }

    public function getMetaDescription(): string
    {
        return 'Électroménager, mobilier, informatique : comparez les prêts personnels de nos partenaires dès 300 € et obtenez une réponse de principe immédiate.';
    }

    public function getHeading(): string
    {
        return 'Prêt personnel : équipez votre foyer sans attendre';
    }

    public function getTagline(): string
    {
        return 'Mobilier, électroménager, high-tech.';
    }

    public function getIntroduction(): array
    {
        return [
            'Le prêt personnel finance l\'équipement de votre foyer avec des mensualités fixes. Les petits montants sont accessibles sur des durées courtes.',
            'Le comparateur calcule pour vous la mensualité, le TAEG et le coût total de chaque offre à partir de votre situation réelle.',
        ];
    }

    public function getUseCases(): array
    {
        return ['Électroménager', 'Mobilier et literie', 'Informatique et high-tech', 'Équipement de loisirs', 'Instrument de musique'];
    }

    public function getFaq(): array
    {
        return [
            ['question' => 'Quel est le montant minimum ?', 'answer' => 'Certains partenaires financent des projets dès 300 €, sur 6 à 36 mois.'],
            ['question' => 'Dois-je justifier l\'utilisation des fonds ?', 'answer' => 'Vous indiquez votre projet pour obtenir les offres adaptées ; les justificatifs demandés dépendent du partenaire.'],
            ['question' => 'Combien de temps pour obtenir une réponse ?', 'answer' => 'Le comparateur affiche une réponse de principe dès la fin du formulaire. L\'offre définitive est émise par le partenaire après étude du dossier.'],
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
