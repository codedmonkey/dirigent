<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionProvideLink extends AbstractVersionLink
{
    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'provide')]
    #[ORM\JoinColumn(nullable: false)]
    protected Version $version;

    public function isImplementation(): bool
    {
        return str_ends_with($this->getLinkedPackageName(), '-implementation');
    }

    public function getImplementedPackageName(): string
    {
        return substr($this->getLinkedPackageName(), 0, -15);
    }
}
