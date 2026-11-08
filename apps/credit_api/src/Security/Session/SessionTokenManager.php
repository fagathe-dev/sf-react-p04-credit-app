<?php

declare(strict_types=1);

namespace App\Security\Session;

use App\Security\User\SessionUserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Émet et lit le JWT de session (lexik/jwt-authentication-bundle), signé avec les clés de config/jwt.
 */
final readonly class SessionTokenManager
{
    public function __construct(
        private JWTEncoderInterface $encoder,
        #[Autowire(env: 'int:JWT_TTL')]
        private int $ttl,
    ) {}

    public function issue(SessionUserInterface $user, SessionAudienceEnum $audience): string
    {
        $now = time();

        return $this->encoder->encode([
            'sub' => $user->getUserIdentifier(),
            'aud' => $audience->value,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ]);
    }

    /**
     * Identifiant de l'utilisateur si le jeton est valide pour ce public, null sinon.
     */
    public function readSubject(string $token, SessionAudienceEnum $audience): ?string
    {
        try {
            $payload = $this->encoder->decode($token);
        } catch (JWTDecodeFailureException) {
            return null;
        }

        $tokenAudience = $payload['aud'] ?? null;
        $audiences = \is_array($tokenAudience) ? $tokenAudience : [$tokenAudience];

        if (!\in_array($audience->value, $audiences, true) || !\is_string($payload['sub'] ?? null)) {
            return null;
        }

        return $payload['sub'];
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }
}
