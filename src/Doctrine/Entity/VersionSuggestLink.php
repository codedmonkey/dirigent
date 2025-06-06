<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionSuggestLink extends AbstractVersionLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'suggest')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
