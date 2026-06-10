<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PackageProvideLink extends AbstractPackageLink
{
    #[ORM\Id, ORM\ManyToOne(targetEntity: Package::class)]
    #[\Override]
    protected Package $package;

    #[ORM\Id, ORM\Column]
    private bool $implementation;

    public function isImplementation(): bool
    {
        return $this->implementation;
    }

    public function setImplementation(bool $implementation): static
    {
        $this->implementation = $implementation;

        return $this;
    }
}
