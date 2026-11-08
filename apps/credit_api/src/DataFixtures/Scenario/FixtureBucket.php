<?php

declare(strict_types=1);

namespace App\DataFixtures\Scenario;

use App\Crm\Priority\PriorityAssessment;
use App\Entity\Dossier;

/**
 * Un cas de figure du jeu de données : nombre de dossiers, profil de demande, fenêtre d'ancienneté,
 * critère d'acceptation (vérifié sur le dossier réellement calculé) et action à rejouer ensuite.
 */
final readonly class FixtureBucket
{
    public const string ACTION_NONE = 'none';
    public const string ACTION_SUBSCRIBE = 'subscribe';
    public const string ACTION_APPROVE = 'approve';
    public const string ACTION_REJECT_SUBSCRIPTION = 'reject_subscription';

    /**
     * @param list<string>                                       $profiles profils ApplicantRequestGenerator tirés au hasard
     * @param \Closure(Dossier, PriorityAssessment): bool         $accept
     * @param bool|null                                          $consent  consentement de rappel (null : tiré au hasard)
     */
    public function __construct(
        public string $name,
        public int $count,
        public array $profiles,
        public int $minAgeSeconds,
        public int $maxAgeSeconds,
        public \Closure $accept,
        public ?bool $consent = true,
        public string $action = self::ACTION_NONE,
        public bool $pendingVerification = false,
        /** Suivi d'appel clos autorisé (sort le dossier de la priorisation). */
        public bool $allowClosedCallback = false,
    ) {}
}
