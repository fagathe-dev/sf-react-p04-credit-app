<?php

declare(strict_types=1);

namespace App\Security\Auth;

use App\ClientSpace\OtpService;
use App\ClientSpace\OtpVerificationStatusEnum;
use App\DTO\Request\OtpRequestDto;
use App\DTO\Request\OtpVerifyDto;
use App\Http\ApiResponseTrait;
use App\Http\PayloadReader;
use App\Security\Session\SessionAudienceEnum;
use App\Security\Session\SessionCookieFactory;
use App\Security\Session\SessionTokenManager;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Connexion par code OTP (demande, vérification, déconnexion), commune aux clients et aux conseillers.
 */
final class OtpLoginService
{
    use ApiResponseTrait;

    /** @var array<string, OtpAudienceInterface> */
    private array $audiences = [];

    /**
     * @param iterable<OtpAudienceInterface> $audiences
     */
    public function __construct(
        #[AutowireIterator(OtpAudienceInterface::TAG)]
        iterable $audiences,
        private readonly PayloadReader $payloadReader,
        private readonly OtpService $otpService,
        private readonly SessionTokenManager $tokenManager,
        private readonly SessionCookieFactory $cookieFactory,
    ) {
        foreach ($audiences as $audience) {
            $this->audiences[$audience->getAudience()->value] = $audience;
        }
    }

    /**
     * Répond toujours 204, que l'adresse corresponde à un compte ou non.
     */
    public function requestCode(SessionAudienceEnum $audience, Request $request): object
    {
        $payload = $this->payloadReader->read($request, OtpRequestDto::class);
        if (!$payload->isValid()) {
            return $this->sendUnprocessable($payload->violations);
        }

        /** @var OtpRequestDto $dto */
        $dto = $payload->dto;
        $strategy = $this->audience($audience);
        $user = $strategy->findEligibleUser($dto->email);

        if (null !== $user) {
            $dossier = $strategy->resolveDossier($user, $dto->dossierId);
            $this->otpService->issue($user, $strategy->getLoginUrl($user, $dossier), $dossier);
        }

        return $this->sendNoContent();
    }

    public function verifyCode(SessionAudienceEnum $audience, Request $request): object
    {
        $payload = $this->payloadReader->read($request, OtpVerifyDto::class);
        if (!$payload->isValid()) {
            return $this->sendUnprocessable($payload->violations);
        }

        /** @var OtpVerifyDto $dto */
        $dto = $payload->dto;
        $strategy = $this->audience($audience);
        $user = $strategy->findEligibleUser($dto->email);

        if (null === $user) {
            return $this->sendUnauthorized('Code invalide ou expiré.');
        }

        [$status, $otp] = $this->otpService->verify($user, $dto->code);

        if (OtpVerificationStatusEnum::TOO_MANY_ATTEMPTS === $status) {
            return $this->sendTooManyRequests('Trop d\'essais : ce code n\'est plus valable. Demandez un nouveau code.');
        }

        if (OtpVerificationStatusEnum::VALID !== $status || null === $otp) {
            return $this->sendUnauthorized('Code invalide ou expiré.');
        }

        $data = $strategy->onVerified($user, $otp);
        $cookie = $this->cookieFactory->create($audience, $this->tokenManager->issue($user, $audience));

        return $this->sendJson($data, headers: ['Set-Cookie' => (string) $cookie]);
    }

    public function logout(SessionAudienceEnum $audience): object
    {
        return $this->sendNoContent(['Set-Cookie' => (string) $this->cookieFactory->clear($audience)]);
    }

    private function audience(SessionAudienceEnum $audience): OtpAudienceInterface
    {
        return $this->audiences[$audience->value] ?? throw new \LogicException(\sprintf('Aucune stratégie OTP pour le public "%s".', $audience->value));
    }
}
