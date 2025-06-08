<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionReplaceLink extends AbstractVersionLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'replace')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;
}
