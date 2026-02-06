<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractMetadataLink
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    protected Metadata $metadata;

    #[ORM\Column(length: 191)]
    private string $linkedPackageName;

    #[ORM\Column(type: Types::TEXT)]
    private string $linkedVersionConstraint;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getLinkedPackageName(): string
    {
        return $this->linkedPackageName;
    }

    public function setLinkedPackageName(string $packageName): void
    {
        $this->linkedPackageName = $packageName;
    }

    public function getLinkedVersionConstraint(): string
    {
        return $this->linkedVersionConstraint;
    }

    public function setLinkedVersionConstraint(string $packageVersion): void
    {
        $this->linkedVersionConstraint = $packageVersion;
    }
}
