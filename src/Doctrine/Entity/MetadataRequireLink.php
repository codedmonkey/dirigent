<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MetadataRequireLink extends AbstractMetadataLink
{
    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'requireLinks')]
    #[ORM\JoinColumn(nullable: false)]
    protected Metadata $metadata;
}
