<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PackageInstallations extends AbstractInstallations
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'installations')]
    private Package $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }
}
