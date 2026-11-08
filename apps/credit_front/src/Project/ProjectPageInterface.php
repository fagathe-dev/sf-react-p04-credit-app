<?php

declare(strict_types=1);

namespace App\Project;

use Credit\Core\Enum\CreditProjectTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contenu éditorial d'une page projet. Ajouter une page = ajouter une classe :
 * ProjectPageRegistry, le contrôleur, le sitemap et la navigation la découvrent automatiquement.
 */
#[AutoconfigureTag(self::TAG)]
interface ProjectPageInterface
{
    public const TAG = 'app.project_page';

    /** Chemin de la page, sans barre oblique (ex. credit-vert). */
    public function getSlug(): string;

    public function getProjectType(): CreditProjectTypeEnum;

    /** Ordre d'affichage dans la navigation. */
    public function getPosition(): int;

    public function getMetaTitle(): string;

    public function getMetaDescription(): string;

    public function getHeading(): string;

    /** Accroche courte (cartes de l'accueil). */
    public function getTagline(): string;

    /** @return list<string> paragraphes d'explication (texte brut) */
    public function getIntroduction(): array;

    /** @return list<string> exemples de projets financés */
    public function getUseCases(): array;

    /** @return list<array{question: string, answer: string}> */
    public function getFaq(): array;

    /** Montant (centimes) et durée proposés par défaut dans le lien vers le tunnel. */
    public function getDefaultAmountCents(): int;

    public function getDefaultDurationMonths(): int;
}
