<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PackageInstallations extends AbstractInstallations
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'installations')]
    private Package $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }
}
