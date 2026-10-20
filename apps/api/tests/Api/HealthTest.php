<?php

namespace App\Tests\Api;

final class HealthTest extends JsonApiTestCase
{
    public function testAC01HealthReportsApiAndDatabaseOk(): void
    {
        static::createJsonClient()->request('GET', '/health');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonEquals(['status' => 'ok', 'database' => 'ok']);
    }
}
