<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Session\SessionAudienceEnum;
use App\Security\Session\SessionTokenManager;
use App\Security\User\SessionUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Authentification par cookie de session JWT. Un cookie invalide ou expiré n'interrompt pas
 * la requête : les routes publiques restent accessibles, les routes protégées répondent 401.
 */
abstract class AbstractCookieAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly SessionTokenManager $tokenManager,
    ) {}

    abstract protected function getAudience(): SessionAudienceEnum;

    abstract protected function findUser(Uuid $id): ?SessionUserInterface;

    public function supports(Request $request): ?bool
    {
        return '' !== (string) $request->cookies->get($this->getAudience()->cookieName(), '');
    }

    public function authenticate(Request $request): Passport
    {
        $token = (string) $request->cookies->get($this->getAudience()->cookieName(), '');
        $subject = $this->tokenManager->readSubject($token, $this->getAudience());

        if (null === $subject || !Uuid::isValid($subject)) {
            throw new CustomUserMessageAuthenticationException('Session invalide ou expirée.');
        }

        return new SelfValidatingPassport(new UserBadge($subject, function (string $identifier): SessionUserInterface {
            $user = $this->findUser(Uuid::fromString($identifier));

            if (null === $user || !$user->isSessionAllowed()) {
                throw new CustomUserMessageAuthenticationException('Session invalide ou expirée.');
            }

            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // La requête continue sans utilisateur : access_control décide (401 via start())
        return null;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['message' => 'Authentification requise.', 'code' => 'unauthenticated'], Response::HTTP_UNAUTHORIZED);
    }
}
