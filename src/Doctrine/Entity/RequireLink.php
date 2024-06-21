<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RequireLink extends PackageLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'require')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
