<?php

namespace App\Serializer;

use App\Entity\Task;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * RM-05 : l'id envoyé par le client est ignoré.
 *
 * API Platform rejette sinon tout corps contenant un id en 400 (« Update is not allowed »)
 * avant même d'appliquer les groupes de dénormalisation.
 */
final class IgnoreClientTaskIdDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\is_array($data)) {
            unset($data['id']);
        }

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Task::class === $type && \is_array($data) && \array_key_exists('id', $data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Task::class => false];
    }
}
