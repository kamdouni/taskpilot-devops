<?php

namespace App\Tests\Api;

final class TaskDeleteTest extends JsonApiTestCase
{
    public function testAC15DeletesTaskThenReturnsNotFound(): void
    {
        $created = self::createTask();
        $client = static::createJsonClient();

        $response = $client->request('DELETE', '/api/tasks/'.$created['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $response->getContent());

        $client->request('GET', '/api/tasks/'.$created['id']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testAC15DeletingUnknownTaskReturnsNotFound(): void
    {
        static::createJsonClient()->request('DELETE', '/api/tasks/01890000-0000-7000-8000-000000000000');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }
}
