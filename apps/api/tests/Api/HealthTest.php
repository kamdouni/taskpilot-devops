<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;

final class HealthTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testAC01HealthReportsApiAndDatabaseOk(): void
    {
        static::createClient()->request('GET', '/health');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonEquals(['status' => 'ok', 'database' => 'ok']);
    }
}
