<?php

namespace App\Tests\Api;

use Symfony\Component\Uid\Uuid;

final class TaskCreateTest extends JsonApiTestCase
{
    public function testAC02CreatesTaskWithDefaults(): void
    {
        $response = static::createJsonClient()->request('POST', '/api/tasks', [
            'json' => ['title' => 'Rédiger la spec'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertStringStartsWith('application/json', $response->getHeaders()['content-type'][0]);

        $task = $response->toArray();
        self::assertIsString($task['id']);
        self::assertTrue(Uuid::isValid($task['id']));
        self::assertSame('Rédiger la spec', $task['title']);
        self::assertSame('todo', $task['status']);
        self::assertSame('medium', $task['priority']);
        self::assertArrayHasKey('description', $task);
        self::assertNull($task['description']);
        self::assertArrayHasKey('dueDate', $task);
        self::assertNull($task['dueDate']);
        self::assertIsString($task['createdAt']);
        self::assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $task['createdAt']));
        self::assertIsString($task['updatedAt']);
        self::assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $task['updatedAt']));
    }
}
