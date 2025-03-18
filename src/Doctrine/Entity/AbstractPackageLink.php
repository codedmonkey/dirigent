<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractPackageLink
{
    protected Package $package;

    #[ORM\Id, ORM\Column(length: 191)]
    private string $linkedPackageName;

    public function getPackage(): Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): void
    {
        $this->package = $package;
    }

    public function getLinkedPackageName(): string
    {
        return $this->linkedPackageName;
    }

    public function setLinkedPackageName(string $packageName): void
    {
        $this->linkedPackageName = $packageName;
    }
}
