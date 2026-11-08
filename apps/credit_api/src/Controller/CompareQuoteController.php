<?php

declare(strict_types=1);

namespace App\Controller;

use App\Comparison\ComparisonSubmissionService;
use App\Entity\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CompareQuoteController extends AbstractApiController
{
    public function __construct(
        private readonly ComparisonSubmissionService $submissionService,
    ) {}

    #[Route('/api/v1/compare', name: 'api_compare', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();

        return $this->respond($this->submissionService->submit($request, $user instanceof Client ? $user : null));
    }
}
