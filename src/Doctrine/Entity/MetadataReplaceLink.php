<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class MetadataReplaceLink extends AbstractMetadataLink
{
    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'replace')]
    #[ORM\JoinColumn(nullable: false)]
    protected Metadata $metadata;
}
