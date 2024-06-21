<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SuggestLink extends PackageLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'suggest')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
