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

    #[ORM\Column]
    private int $index;

    public function __construct(
        Metadata $metadata,
        string $linkedPackageName,
        string $linkedVersionConstraint,
        int $index,
    ) {
        $this->metadata = $metadata;
        $this->linkedPackageName = $linkedPackageName;
        $this->linkedVersionConstraint = $linkedVersionConstraint;
        $this->index = $index;

        $this->addToCollection();
    }

    abstract protected function addToCollection(): void;

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

    public function getLinkedVersionConstraint(): string
    {
        return $this->linkedVersionConstraint;
    }

    public function getIndex(): int
    {
        return $this->index;
    }
}
