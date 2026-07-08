<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DistributionRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[ORM\UniqueConstraint(columns: ['metadata_id', 'reference', 'type'])]
class Distribution extends TrackedEntity
{
    #[ORM\Column, ORM\GeneratedValue, ORM\Id]
    private ?int $id = null;

    #[ORM\Column]
    private string $reference;

    #[ORM\Column]
    private string $type;

    #[ORM\Column(nullable: true)]
    private ?string $sha1Checksum = null;

    /**
     * Source URL.
     *
     * Contains the source URL if the distribution is mirrored.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $source = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\ManyToOne(inversedBy: 'distributions')]
    #[ORM\JoinColumn(nullable: false)]
    private Metadata $metadata;

    public function __construct(Metadata $metadata, string $reference, string $type)
    {
        $this->metadata = $metadata;
        $this->reference = $reference;
        $this->type = $type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSha1Checksum(): ?string
    {
        return $this->sha1Checksum;
    }

    public function setSha1Checksum(?string $sha1Checksum): void
    {
        $this->sha1Checksum = $sha1Checksum;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
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
