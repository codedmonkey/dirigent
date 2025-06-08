<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PackageSuggestLink extends AbstractPackageLink
{
    #[ORM\Id, ORM\ManyToOne(targetEntity: Package::class), ORM\JoinColumn(nullable: false)]
    protected Package $package;
}
