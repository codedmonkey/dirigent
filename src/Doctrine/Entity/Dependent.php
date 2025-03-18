<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Dependent
{
    #[ORM\Id, ORM\ManyToOne(targetEntity: Package::class), ORM\JoinColumn(nullable: false)]
    private ?Package $package = null;

    #[ORM\Id, ORM\Column(length: 191)]
    private string $dependentPackageName;

    #[ORM\Id, ORM\Column]
    private bool $devDependency;

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): static
    {
        $this->package = $package;

        return $this;
    }

    public function getDependentPackageName(): string
    {
        return $this->dependentPackageName;
    }

    public function setDependentPackageName(string $packageName): static
    {
        $this->dependentPackageName = $packageName;

        return $this;
    }

    public function isDevDependency(): bool
    {
        return $this->devDependency;
    }

    public function setDevDependency(bool $devDependency): static
    {
        $this->devDependency = $devDependency;

        return $this;
    }
}
