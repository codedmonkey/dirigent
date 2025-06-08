<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionDevRequireLink extends AbstractVersionLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'devRequire')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
