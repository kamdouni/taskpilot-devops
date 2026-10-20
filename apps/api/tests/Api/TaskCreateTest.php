<?php

namespace App\Tests\Api;

use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('blankTitleProvider')]
    public function testAC03RejectsMissingOrBlankTitle(array $payload): void
    {
        $response = static::createJsonClient()->request('POST', '/api/tasks', ['json' => $payload]);

        self::assertViolationOn($response, 'title');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function blankTitleProvider(): iterable
    {
        yield 'absent' => [['description' => 'Sans titre']];
        yield 'vide' => [['title' => '']];
        yield 'espaces' => [['title' => '   ']];
    }

    public function testAC03TitleIsTrimmed(): void
    {
        $response = static::createJsonClient()->request('POST', '/api/tasks', [
            'json' => ['title' => '  Rédiger la spec  '],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Rédiger la spec', $response->toArray()['title']);
    }

    public function testAC04RejectsTitleLongerThan255Characters(): void
    {
        $response = static::createJsonClient()->request('POST', '/api/tasks', [
            'json' => ['title' => str_repeat('a', 256)],
        ]);

        self::assertViolationOn($response, 'title');
    }

    public function testAC04Accepts255CharacterTitleAfterTrim(): void
    {
        static::createJsonClient()->request('POST', '/api/tasks', [
            'json' => ['title' => ' '.str_repeat('a', 255).' '],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    public function testAC05RejectsStatusOutsideEnumeration(): void
    {
        $response = static::createJsonClient()->request('POST', '/api/tasks', [
            'json' => ['title' => 'Rédiger la spec', 'status' => 'archived'],
        ]);

        self::assertViolationOn($response, 'status');
    }

    public function testAC05RejectsPriorityOutsideEnumeration(): void
    {
        $response = static::createJsonClient()->request('POST', '/api/tasks', [
            'json' => ['title' => 'Rédiger la spec', 'priority' => 'urgent'],
        ]);

        self::assertViolationOn($response, 'priority');
    }

    public function testAC05AcceptsStatusAndPriorityFromEnumeration(): void
    {
        $response = static::createJsonClient()->request('POST', '/api/tasks', [
            'json' => ['title' => 'Rédiger la spec', 'status' => 'in_progress', 'priority' => 'high'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('in_progress', $response->toArray()['status']);
        self::assertSame('high', $response->toArray()['priority']);
    }
}
