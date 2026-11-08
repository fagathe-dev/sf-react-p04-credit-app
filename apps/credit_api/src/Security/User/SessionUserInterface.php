<?php

declare(strict_types=1);

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Utilisateur authentifié par cookie de session JWT (Client ou Advisor).
 */
interface SessionUserInterface extends UserInterface
{
    public function getId(): Uuid;

    public function getEmail(): string;

    public function getFullName(): string;

    /** Faux pour un compte désactivé : la session est refusée. */
    public function isSessionAllowed(): bool;
}
