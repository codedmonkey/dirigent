<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MetadataSuggestLink extends AbstractMetadataLink
{
    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'suggestLinks')]
    #[ORM\JoinColumn(nullable: false)]
    protected Metadata $metadata;
}
