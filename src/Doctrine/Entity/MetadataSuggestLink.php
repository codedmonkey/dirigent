<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MetadataSuggestLink extends AbstractMetadataLink
{
    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'suggest')]
    #[ORM\JoinColumn(nullable: false)]
    protected Metadata $metadata;

    #[\Override]
    protected function addToCollection(): void
    {
        $this->metadata->getSuggest()->add($this);
    }
}
