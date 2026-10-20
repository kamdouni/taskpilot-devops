<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [new GetCollection(), new Get(), new Post()],
    // RM-03 ; createdAt est à la seconde, l'id (UUID v7, ordonné dans le temps) départage les égalités
    order: ['createdAt' => 'DESC', 'id' => 'DESC'],
    formats: ['json'],
    normalizationContext: ['groups' => ['task:read']],
    // RM-05 : id, createdAt et updatedAt ne sont pas dans le groupe d'écriture, donc ignorés
    denormalizationContext: ['groups' => ['task:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact', 'priority' => 'exact'])]
class Task
{
    public const array STATUSES = ['todo', 'in_progress', 'done'];
    public const array PRIORITIES = ['low', 'medium', 'high'];

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['task:read'])]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['task:read', 'task:write'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::STATUSES)]
    #[Groups(['task:read', 'task:write'])]
    private string $status = 'todo';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::PRIORITIES)]
    #[Groups(['task:read', 'task:write'])]
    private string $priority = 'medium';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[Groups(['task:read', 'task:write'])]
    private ?\DateTimeImmutable $dueDate = null;

    /** Vrai si dueDate a été définie depuis le chargement : seule une nouvelle date est validée. */
    private bool $dueDateChanged = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['task:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['task:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /** RM-01 : le titre est nettoyé avant validation. */
    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): void
    {
        $this->priority = $priority;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): void
    {
        if ($dueDate?->format('Y-m-d') !== $this->dueDate?->format('Y-m-d')) {
            $this->dueDateChanged = true;
        }
        $this->dueDate = $dueDate;
    }

    /** AC-06 : une date d'échéance ne peut pas être définie dans le passé. */
    #[Assert\Callback]
    public function validateDueDate(ExecutionContextInterface $context): void
    {
        if ($this->dueDateChanged && null !== $this->dueDate && $this->dueDate < new \DateTimeImmutable('today')) {
            $context->buildViolation('La date d\'échéance ne peut pas être dans le passé.')
                ->atPath('dueDate')
                ->addViolation();
        }
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
