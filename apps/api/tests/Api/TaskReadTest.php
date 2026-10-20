<?php

namespace App\Tests\Api;

final class TaskReadTest extends JsonApiTestCase
{
    public function testAC08ReturnsExistingTask(): void
    {
        $created = self::createTask([
            'title' => 'Rédiger la spec',
            'description' => 'Critères AC',
            'priority' => 'high',
            'dueDate' => (new \DateTimeImmutable('tomorrow'))->format('Y-m-d'),
        ]);

        $response = static::createJsonClient()->request('GET', '/api/tasks/'.$created['id']);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($created, $response->toArray());
    }

    public function testAC09ReturnsNotFoundForUnknownUuid(): void
    {
        static::createJsonClient()->request('GET', '/api/tasks/01890000-0000-7000-8000-000000000000');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }
}
