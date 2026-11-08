<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Désérialise le corps JSON d'une requête vers un DTO puis le valide.
 * Toutes les erreurs (JSON invalide, type incorrect, contrainte) sont rendues sous la forme champ => message.
 */
final readonly class PayloadReader
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    /**
     * @param class-string $class
     */
    public function read(Request $request, string $class): PayloadResult
    {
        $content = trim($request->getContent());

        try {
            $dto = $this->serializer->deserialize('' === $content ? '{}' : $content, $class, 'json', [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);
        } catch (PartialDenormalizationException $e) {
            $violations = [];
            foreach ($e->getNotNormalizableValueErrors() as $error) {
                $path = $error->getPath() ?? 'payload';
                $violations[$path] = null === $error->getCurrentType() || 'null' === $error->getCurrentType()
                    ? 'Ce champ est obligatoire.'
                    : 'Valeur invalide.';
            }

            return PayloadResult::invalid($violations ?: ['payload' => 'Données invalides.']);
        } catch (NotEncodableValueException) {
            return PayloadResult::invalid(['payload' => 'Le corps de la requête doit être un JSON valide.']);
        } catch (SerializerException $e) {
            return PayloadResult::invalid(['payload' => self::missingFieldMessage($e)]);
        }

        $violations = [];
        foreach ($this->validator->validate($dto) as $violation) {
            $path = $violation->getPropertyPath();
            // Contrainte de méthode isXxxConsistent() : rattachée au champ xxx
            $path = '' === $path ? 'payload' : (string) preg_replace('/Consistent$/', '', $path);
            $violations[$path] ??= (string) $violation->getMessage();
        }

        return [] === $violations ? PayloadResult::valid($dto) : PayloadResult::invalid($violations);
    }

    private static function missingFieldMessage(\Throwable $e): string
    {
        // ex. « Cannot create an instance of "…" from serialized data because its constructor requires the following parameters to be present : "$email". »
        if (preg_match_all('/"\$(\w+)"/', $e->getMessage(), $matches) > 0) {
            return \sprintf('Champs obligatoires manquants : %s.', implode(', ', $matches[1]));
        }

        return 'Données invalides.';
    }
}
