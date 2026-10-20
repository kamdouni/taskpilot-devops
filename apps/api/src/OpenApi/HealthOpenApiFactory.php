<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * Ajoute GET /health (contrôleur Symfony, hors API Platform) à la documentation générée.
 */
#[AsDecorator('api_platform.openapi.factory')]
final class HealthOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $schema = new \ArrayObject([
            'type' => 'object',
            'required' => ['status', 'database'],
            'properties' => [
                'status' => ['type' => 'string', 'enum' => ['ok']],
                'database' => ['type' => 'string', 'enum' => ['ok']],
            ],
        ]);

        $openApi->getPaths()->addPath('/health', (new PathItem())->withGet(new Operation(
            operationId: 'getHealth',
            tags: ['Health'],
            responses: [
                '200' => new Response(
                    description: 'API opérationnelle',
                    content: new \ArrayObject(['application/json' => new MediaType(schema: $schema)]),
                ),
            ],
            summary: "État de l'API et de la base (AC-01)",
        )));

        return $openApi;
    }
}
