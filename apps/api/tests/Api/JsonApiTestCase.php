<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;

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
}
