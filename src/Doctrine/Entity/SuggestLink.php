<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SuggestLink extends AbstractPackageLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'suggest')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
