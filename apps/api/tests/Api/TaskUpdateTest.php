<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;

final class TaskUpdateTest extends JsonApiTestCase
{
    public function testAC13PatchesStatusAndKeepsOtherFields(): void
    {
        $created = self::createTask([
            'title' => 'Rédiger la spec',
            'description' => 'Critères AC',
            'priority' => 'high',
            'dueDate' => (new \DateTimeImmutable('tomorrow'))->format('Y-m-d'),
        ]);

        $response = self::patchTask($created['id'], ['status' => 'done']);

        self::assertResponseStatusCodeSame(200);
        $updated = $response->toArray();
        self::assertSame('done', $updated['status']);
        foreach (['id', 'title', 'description', 'priority', 'dueDate', 'createdAt'] as $field) {
            self::assertSame($created[$field], $updated[$field], $field.' ne doit pas changer');
        }
        self::assertIsString($updated['createdAt']);
        self::assertIsString($updated['updatedAt']);
        self::assertGreaterThanOrEqual(new \DateTimeImmutable($updated['createdAt']), new \DateTimeImmutable($updated['updatedAt']));
    }

    public function testAC13RefreshesUpdatedAtAutomatically(): void
    {
        $created = self::createTask();
        self::connection()->executeStatement(
            "UPDATE task SET created_at = '2020-01-01 00:00:00', updated_at = '2020-01-01 00:00:00' WHERE id = :id",
            ['id' => $created['id']],
        );

        $updated = self::patchTask($created['id'], ['status' => 'in_progress'])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertIsString($updated['updatedAt']);
        self::assertGreaterThan(new \DateTimeImmutable('2020-01-01'), new \DateTimeImmutable($updated['updatedAt']));
    }

    public function testAC13PastDueDateDoesNotBlockStatusChange(): void
    {
        $created = self::createTask(['title' => 'Ancienne', 'dueDate' => (new \DateTimeImmutable('today'))->format('Y-m-d')]);
        self::connection()->executeStatement(
            "UPDATE task SET due_date = '2020-01-01' WHERE id = :id",
            ['id' => $created['id']],
        );

        $updated = self::patchTask($created['id'], ['status' => 'done'])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('2020-01-01', $updated['dueDate']);
    }

    private static function connection(): Connection
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }

    public function testAC14RejectsBlankTitleAndLeavesTaskUnchanged(): void
    {
        $created = self::createTask(['title' => 'Rédiger la spec', 'priority' => 'high']);

        $response = self::patchTask($created['id'], ['title' => '', 'status' => 'done']);

        self::assertViolationOn($response, 'title');
        $current = static::createJsonClient()->request('GET', '/api/tasks/'.$created['id'])->toArray();
        self::assertSame($created, $current);
    }

    public function testAC14RejectsNewPastDueDateOnPatch(): void
    {
        $created = self::createTask();

        $response = self::patchTask($created['id'], ['dueDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d')]);

        self::assertViolationOn($response, 'dueDate');
        $current = static::createJsonClient()->request('GET', '/api/tasks/'.$created['id'])->toArray();
        self::assertSame($created, $current);
    }
}
