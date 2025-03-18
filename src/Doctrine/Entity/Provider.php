<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Provider
{
    #[ORM\Id, ORM\ManyToOne(targetEntity: Package::class), ORM\JoinColumn(nullable: false)]
    private ?Package $package = null;

    #[ORM\Id, ORM\Column(length: 191)]
    private string $providedPackageName;

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): static
    {
        $this->package = $package;

        return $this;
    }

    public function getProvidedPackageName(): string
    {
        return $this->providedPackageName;
    }

    public function setProvidedPackageName(string $packageName): static
    {
        $this->providedPackageName = $packageName;

        return $this;
    }
}
