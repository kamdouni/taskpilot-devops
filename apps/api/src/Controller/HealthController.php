<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** AC-01 : état de l'API et de la base. */
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $this->connection->executeQuery('SELECT 1');

        return new JsonResponse(['status' => 'ok', 'database' => 'ok']);
    }
}
