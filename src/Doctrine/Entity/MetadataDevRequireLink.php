<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MetadataDevRequireLink extends AbstractMetadataLink
{
    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'devRequire')]
    #[ORM\JoinColumn(nullable: false)]
    protected Metadata $metadata;

    #[\Override]
    protected function addToCollection(): void
    {
        $this->metadata->getDevRequire()->add($this);
    }
}
