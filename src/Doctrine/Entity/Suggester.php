<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Suggester
{
    #[ORM\Id, ORM\ManyToOne(targetEntity: Package::class), ORM\JoinColumn(nullable: false)]
    private ?Package $package = null;

    #[ORM\Id, ORM\Column(length: 191)]
    private string $suggestedPackageName;

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): static
    {
        $this->package = $package;

        return $this;
    }

    public function getSuggestedPackageName(): string
    {
        return $this->suggestedPackageName;
    }

    public function setSuggestedPackageName(string $packageName): static
    {
        $this->suggestedPackageName = $packageName;

        return $this;
    }
}
