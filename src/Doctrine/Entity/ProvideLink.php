<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProvideLink extends PackageLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'provide')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
