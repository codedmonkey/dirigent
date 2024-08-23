<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionDownloads extends AbstractDownloads
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'downloads')]
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
