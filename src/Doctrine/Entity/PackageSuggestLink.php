<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PackageSuggestLink extends AbstractPackageLink
{
    #[ORM\Id, ORM\ManyToOne(targetEntity: Package::class)]
    #[\Override]
    protected Package $package;
}
