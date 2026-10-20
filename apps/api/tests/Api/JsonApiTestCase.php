<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * L'API n'expose que du JSON : le client de test négocie application/json au lieu de JSON-LD.
 */
abstract class JsonApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    protected static function createJsonClient(): Client
    {
        return static::createClient([], ['headers' => ['accept' => 'application/json']]);
    }

    /**
     * ENF-02 : 422 au format problem+json avec une violation sur le champ attendu.
     */
    protected static function assertViolationOn(ResponseInterface $response, string $propertyPath): void
    {
        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');

        $problem = $response->toArray(false);
        self::assertIsArray($problem['violations'] ?? null);
        self::assertContains($propertyPath, array_column($problem['violations'], 'propertyPath'));
    }
}
