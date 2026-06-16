<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class MetadataConflictLink extends AbstractMetadataLink
{
    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'conflictLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[\Override]
    protected Metadata $metadata;
}
