<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ConflictLink extends AbstractPackageLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'conflict')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
