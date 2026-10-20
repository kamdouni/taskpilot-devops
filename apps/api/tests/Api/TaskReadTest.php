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

    public function testAC10ListsTasksAsJsonArrayNewestFirst(): void
    {
        $first = self::createTask(['title' => 'Première']);
        $second = self::createTask(['title' => 'Deuxième']);
        $third = self::createTask(['title' => 'Troisième']);

        $response = static::createJsonClient()->request('GET', '/api/tasks');

        self::assertResponseStatusCodeSame(200);
        $tasks = $response->toArray();
        self::assertTrue(array_is_list($tasks), 'La collection doit être un tableau JSON, pas un objet');
        self::assertSame([$third['id'], $second['id'], $first['id']], array_column($tasks, 'id'));

        $createdAts = array_column($tasks, 'createdAt');
        $sorted = $createdAts;
        usort($sorted, static fn (string $a, string $b): int => new \DateTimeImmutable($b) <=> new \DateTimeImmutable($a));
        self::assertSame($sorted, $createdAts);
    }
}
