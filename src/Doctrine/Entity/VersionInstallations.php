<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionInstallations extends AbstractInstallations
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'installations')]
    private Version $version;

    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
