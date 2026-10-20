<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * AC-06 / risque du plan : une dueDate déjà passée ne bloque pas la modification d'autres champs.
 */
final class TaskDueDateValidationTest extends KernelTestCase
{
    public function testAC06PastDueDateIsNotRevalidatedWhenUnchanged(): void
    {
        $task = $this->loadTaskWithPastDueDate();

        $task->setStatus('done');

        self::assertCount(0, $this->validator()->validate($task));
    }

    public function testAC06PastDueDateIsRejectedWhenChanged(): void
    {
        $task = $this->loadTaskWithPastDueDate();

        $task->setDueDate(new \DateTimeImmutable('-2 days'));

        $violations = $this->validator()->validate($task);
        self::assertCount(1, $violations);
        self::assertSame('dueDate', $violations->get(0)->getPropertyPath());
    }

    private function loadTaskWithPastDueDate(): Task
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        $task = new Task();
        $task->setTitle('Tâche ancienne');
        $em->persist($task);
        $em->flush();

        // Simule une tâche créée quand sa date d'échéance était encore future
        $em->getConnection()->executeStatement(
            'UPDATE task SET due_date = :past WHERE id = :id',
            ['past' => (new \DateTimeImmutable('-1 month'))->format('Y-m-d'), 'id' => $task->getId()->toRfc4122()],
        );
        $em->clear();

        $loaded = $em->find(Task::class, $task->getId());
        self::assertInstanceOf(Task::class, $loaded);

        return $loaded;
    }

    private function validator(): ValidatorInterface
    {
        $validator = self::getContainer()->get(ValidatorInterface::class);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        return $validator;
    }
}
