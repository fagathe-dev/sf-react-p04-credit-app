<?php

declare(strict_types=1);

namespace App\Security\Session;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Cookies de session : HttpOnly, SameSite=Lax, Secure selon AUTH_COOKIE_SECURE,
 * posés sur le domaine parent AUTH_COOKIE_DOMAIN (vide en local).
 */
final readonly class SessionCookieFactory
{
    private ?string $domain;

    public function __construct(
        private SessionTokenManager $tokenManager,
        #[Autowire(env: 'bool:AUTH_COOKIE_SECURE')]
        private bool $secure,
        #[Autowire(env: 'AUTH_COOKIE_DOMAIN')]
        ?string $domain,
    ) {
        $this->domain = '' === (string) $domain ? null : $domain;
    }

    public function create(SessionAudienceEnum $audience, string $token): Cookie
    {
        return Cookie::create($audience->cookieName())
            ->withValue($token)
            ->withExpires(time() + $this->tokenManager->getTtl())
            ->withPath('/')
            ->withDomain($this->domain)
            ->withSecure($this->secure)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }

    public function clear(SessionAudienceEnum $audience): Cookie
    {
        return Cookie::create($audience->cookieName())
            ->withValue('')
            ->withExpires(1)
            ->withPath('/')
            ->withDomain($this->domain)
            ->withSecure($this->secure)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }
}
