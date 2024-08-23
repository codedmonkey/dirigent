<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PackageDownloads extends AbstractDownloads
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'downloads')]
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
