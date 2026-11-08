<?php

declare(strict_types=1);

namespace App\Security\Auth;

use App\Entity\Advisor;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Entity\OtpCode;
use App\Security\Session\SessionAudienceEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Particularités d'un public pour la connexion par code (client, conseiller).
 * Ajouter un public = une classe : OtpLoginService n'est pas modifié.
 */
#[AutoconfigureTag(self::TAG)]
interface OtpAudienceInterface
{
    public const string TAG = 'app.otp_audience';

    public function getAudience(): SessionAudienceEnum;

    /** Titulaire autorisé à recevoir un code, null sinon (la réponse reste 204). */
    public function findEligibleUser(string $email): Client|Advisor|null;

    /** Dossier en attente à rattacher au code, si la demande en désigne un. */
    public function resolveDossier(Client|Advisor $user, ?string $dossierId): ?Dossier;

    /** Lien de l'e-mail vers la page de saisie du code. */
    public function getLoginUrl(Client|Advisor $user, ?Dossier $dossier): string;

    /**
     * Code vérifié : met à jour le titulaire (dernière connexion, dossier activé…).
     *
     * @return array<string, mixed> données ajoutées à la réponse de vérification
     */
    public function onVerified(Client|Advisor $user, OtpCode $otp): array;
}
