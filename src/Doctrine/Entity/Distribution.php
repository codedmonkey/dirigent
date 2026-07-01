<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DistributionRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[ORM\UniqueConstraint(columns: ['metadata_id', 'type'])]
class Distribution extends TrackedEntity
{
    #[ORM\Column, ORM\GeneratedValue, ORM\Id]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $type;

    /**
     * Source URL.
     *
     * Contains the source URL if the distribution is mirrored.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $source = null;

    #[ORM\Column]
    private \DateTimeImmutable $resolvedAt;

    #[ORM\ManyToOne(inversedBy: 'distributions')]
    #[ORM\JoinColumn(nullable: false)]
    private Metadata $metadata;

    public function __construct(Metadata $metadata, string $type)
    {
        $this->metadata = $metadata;
        $this->type = $type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getResolvedAt(): \DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(): void
    {
        $this->resolvedAt = new \DateTimeImmutable();
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }
}
