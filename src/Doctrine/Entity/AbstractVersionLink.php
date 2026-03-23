<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractVersionLink
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    protected Version $version;

    #[ORM\Column]
    private int $index;

    #[ORM\Column(length: 191)]
    private string $linkedPackageName;

    #[ORM\Column(type: Types::TEXT)]
    private string $linkedVersionConstraint;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setIndex(int $index): void
    {
        $this->index = $index;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function setVersion(Version $version): void
    {
        $this->version = $version;
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
