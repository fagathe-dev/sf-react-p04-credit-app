<?php

declare(strict_types=1);

namespace App\Controller;

use App\Crm\CrmService;
use App\Entity\Advisor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Back-office conseillers : file de rappels, dossier, suivi d'appel (section 8.4).
 */
#[Route('/api/v1/crm', name: 'api_crm_')]
#[IsGranted(Advisor::ROLE)]
final class CrmController extends AbstractApiController
{
    public function __construct(
        private readonly CrmService $crmService,
    ) {}

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] Advisor $advisor): JsonResponse
    {
        return $this->respond($this->crmService->getProfile($advisor));
    }

    #[Route('/callbacks', name: 'callbacks', methods: ['GET'])]
    public function callbacks(Request $request): JsonResponse
    {
        return $this->respond($this->crmService->listCallbacks($request));
    }

    #[Route('/dossiers/{id}', name: 'dossier', methods: ['GET'])]
    public function dossier(string $id): JsonResponse
    {
        return $this->respond($this->crmService->getDossier($id));
    }

    #[Route('/dossiers/{id}/callback', name: 'dossier_callback', methods: ['PATCH'])]
    public function updateCallback(#[CurrentUser] Advisor $advisor, string $id, Request $request): JsonResponse
    {
        return $this->respond($this->crmService->updateCallback($advisor, $id, $request));
    }
}
