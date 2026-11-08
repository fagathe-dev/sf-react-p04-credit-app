<?php

declare(strict_types=1);

namespace App\Controller;

use App\ClientSpace\ClientSpaceService;
use App\Entity\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Espace client : profil, consentements, dossiers (section 7.7).
 */
#[Route('/api/v1/me', name: 'api_me_')]
#[IsGranted(Client::ROLE)]
final class ClientSpaceController extends AbstractApiController
{
    public function __construct(
        private readonly ClientSpaceService $clientSpaceService,
    ) {}

    #[Route('', name: 'profile', methods: ['GET'])]
    public function profile(#[CurrentUser] Client $client): JsonResponse
    {
        return $this->respond($this->clientSpaceService->getProfile($client));
    }

    #[Route('/consents', name: 'consents', methods: ['PATCH'])]
    public function updateConsents(#[CurrentUser] Client $client, Request $request): JsonResponse
    {
        return $this->respond($this->clientSpaceService->updateConsents($client, $request));
    }

    #[Route('/dossiers', name: 'dossiers', methods: ['GET'])]
    public function dossiers(#[CurrentUser] Client $client): JsonResponse
    {
        return $this->respond($this->clientSpaceService->listDossiers($client));
    }

    #[Route('/dossiers/{id}', name: 'dossier', methods: ['GET'])]
    public function dossier(#[CurrentUser] Client $client, string $id): JsonResponse
    {
        return $this->respond($this->clientSpaceService->getDossier($client, $id));
    }

    #[Route('/dossiers/{id}/subscribe', name: 'dossier_subscribe', methods: ['POST'])]
    public function subscribe(#[CurrentUser] Client $client, string $id, Request $request): JsonResponse
    {
        return $this->respond($this->clientSpaceService->subscribe($client, $id, $request));
    }
}
