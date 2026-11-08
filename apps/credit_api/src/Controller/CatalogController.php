<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalog\CatalogService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractApiController
{
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    #[Route('/api/v1/catalog', name: 'api_catalog', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->respond($this->catalogService->getCatalog());
    }
}
