<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PackageRequireLink extends AbstractPackageLink
{
    #[ORM\Id, ORM\ManyToOne(targetEntity: Package::class), ORM\JoinColumn(nullable: false)]
    protected Package $package;

    #[ORM\Id, ORM\Column]
    private bool $devDependency;

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
