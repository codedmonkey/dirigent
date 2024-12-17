<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ReplaceLink extends PackageLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'replace')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
