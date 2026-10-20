<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * T-02 : socle de configuration (formats JSON, pagination, erreurs problem+json, base de test).
 */
final class ApiConfigurationTest extends KernelTestCase
{
    public function testExchangeFormatIsJsonOnly(): void
    {
        $container = self::getContainer();

        self::assertSame(['json' => ['application/json']], $container->getParameter('api_platform.formats'));
        self::assertSame(['json' => ['application/merge-patch+json']], $container->getParameter('api_platform.patch_formats'));
    }

    public function testENF02ErrorsUseProblemJson(): void
    {
        $errorFormats = self::getContainer()->getParameter('api_platform.error_formats');
        self::assertIsArray($errorFormats);

        self::assertSame(['application/problem+json'], $errorFormats['jsonproblem'] ?? null);
        self::assertArrayNotHasKey('jsonld', $errorFormats);
        // API Platform ajoute un format d'erreur « json » qui sert aussi application/problem+json en priorité
        self::assertSame('application/problem+json', $errorFormats['json'][0] ?? null);
    }

    public function testPaginationDefaultsTo20AndIsCappedAt50(): void
    {
        $container = self::getContainer();

        self::assertSame(20, $container->getParameter('api_platform.collection.pagination.items_per_page'));
        self::assertSame(50, $container->getParameter('api_platform.collection.pagination.maximum_items_per_page'));
        self::assertTrue($container->getParameter('api_platform.collection.pagination.client_items_per_page'));
    }

    public function testDedicatedTestDatabaseIsReachable(): void
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        self::assertSame('app_test', $connection->getDatabase());
        self::assertSame(1, (int) $connection->fetchOne('SELECT 1'));
    }
}
