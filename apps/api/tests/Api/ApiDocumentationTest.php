<?php

namespace App\Tests\Api;

/**
 * Documentation interactive : doc générée (/api/docs) et contrat de référence (/api/contract).
 */
final class ApiDocumentationTest extends JsonApiTestCase
{
    public function testGeneratedDocumentationIncludesHealthEndpoint(): void
    {
        $response = static::createClient()->request('GET', '/api/docs', ['headers' => ['accept' => 'application/vnd.openapi+json']]);

        self::assertResponseStatusCodeSame(200);
        $doc = $response->toArray();
        self::assertIsArray($doc['paths']);
        self::assertIsArray($doc['paths']['/health']['get'] ?? null);
        self::assertSame('getHealth', $doc['paths']['/health']['get']['operationId']);
        self::assertArrayHasKey('200', $doc['paths']['/health']['get']['responses']);
    }

    public function testContractFileIsServed(): void
    {
        $response = static::createClient()->request('GET', '/api/contract.yaml');

        self::assertResponseStatusCodeSame(200);
        self::assertStringStartsWith('application/yaml', $response->getHeaders()['content-type'][0]);
        self::assertStringStartsWith('openapi: 3.1.0', $response->getContent());
    }

    public function testContractIsDisplayedInSwaggerUi(): void
    {
        $response = static::createClient()->request('GET', '/api/contract', ['headers' => ['accept' => 'text/html']]);

        self::assertResponseStatusCodeSame(200);
        $html = $response->getContent();
        self::assertStringContainsString('swagger-ui-bundle.js', $html);
        self::assertStringContainsString('/api/contract.yaml', $html);
    }
}
