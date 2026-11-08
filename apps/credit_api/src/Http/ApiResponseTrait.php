<?php

declare(strict_types=1);

namespace App\Http;

use Fagathe\CorePhp\Http\ResponseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réponses des services (objets { data, status, headers, context } de core-php),
 * complétées des erreurs 422 et 429 de l'API.
 */
trait ApiResponseTrait
{
    use ResponseTrait;

    /**
     * @param array<string, string> $violations champ => message
     */
    protected function sendUnprocessable(array $violations, string $message = 'Certaines informations sont invalides.'): object
    {
        return $this->sendJson(['message' => $message, 'code' => 'validation_failed', 'violations' => $violations], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function sendTooManyRequests(string $message): object
    {
        return $this->sendJson(['message' => $message, 'code' => 'too_many_attempts'], Response::HTTP_TOO_MANY_REQUESTS);
    }

    protected function sendNotFound(string $message = 'Ressource introuvable.'): object
    {
        return $this->sendJson(['message' => $message, 'code' => 'not_found'], Response::HTTP_NOT_FOUND);
    }
}
