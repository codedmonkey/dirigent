<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MetadataProvideLink extends AbstractMetadataLink
{
    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'provide')]
    #[ORM\JoinColumn(nullable: false)]
    protected Metadata $metadata;

    #[\Override]
    protected function addToCollection(): void
    {
        $this->metadata->getProvide()->add($this);
    }

    public function isImplementation(): bool
    {
        return str_ends_with($this->getLinkedPackageName(), '-implementation');
    }

    public function getImplementedPackageName(): string
    {
        return substr($this->getLinkedPackageName(), 0, -15);
    }
}
