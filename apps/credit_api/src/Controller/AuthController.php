<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\Auth\OtpLoginService;
use App\Security\Session\SessionAudienceEnum;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Connexion des clients par code OTP (section 7.7).
 */
#[Route('/api/v1/auth', name: 'api_auth_')]
final class AuthController extends AbstractApiController
{
    public function __construct(
        private readonly OtpLoginService $otpLoginService,
    ) {}

    #[Route('/otp/request', name: 'otp_request', methods: ['POST'])]
    public function requestCode(Request $request): JsonResponse
    {
        return $this->respond($this->otpLoginService->requestCode(SessionAudienceEnum::CLIENT, $request));
    }

    #[Route('/otp/verify', name: 'otp_verify', methods: ['POST'])]
    public function verifyCode(Request $request): JsonResponse
    {
        return $this->respond($this->otpLoginService->verifyCode(SessionAudienceEnum::CLIENT, $request));
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->respond($this->otpLoginService->logout(SessionAudienceEnum::CLIENT));
    }
}
