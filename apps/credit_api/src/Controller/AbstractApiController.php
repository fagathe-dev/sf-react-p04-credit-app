<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Les contrôleurs ne contiennent aucune logique : ils transforment le résultat d'un service en réponse JSON.
 */
abstract class AbstractApiController extends AbstractController
{
    protected function respond(object $result): JsonResponse
    {
        return $this->json($result->data, $result->status, $result->headers, $result->context);
    }
}
