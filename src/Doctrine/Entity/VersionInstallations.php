<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionInstallations extends AbstractInstallations
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'installations')]
    private Version $version;

    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
