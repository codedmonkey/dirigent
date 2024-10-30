<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class PackageLink
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 191)]
    private string $packageName;

    #[ORM\Column(type: Types::TEXT)]
    private string $packageVersion;

    /**
     * Base property holding the version - this must remain protected since it
     * is redefined with an attribute in the child class.
     */
    protected Version $version;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function setPackageName(string $packageName): void
    {
        $this->packageName = $packageName;
    }

    public function getPackageVersion(): string
    {
        return $this->packageVersion;
    }

    public function setPackageVersion(string $packageVersion): void
    {
        $this->packageVersion = $packageVersion;
    }

    public function getVersion(): ?Version
    {
        return $this->version;
    }

    public function setVersion(Version $version): void
    {
        $this->version = $version;
    }

    /**
     * @return non-empty-array<string, string>
     */
    public function toArray(): array
    {
        return [$this->getPackageName() => $this->getPackageVersion()];
    }
}
