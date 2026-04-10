<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MetadataKeyword
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Metadata::class, inversedBy: 'keywords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Metadata $metadata;

    #[ORM\ManyToOne(targetEntity: Keyword::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Keyword $keyword;

    #[ORM\Column]
    private int $index;

    public function __construct(
        Metadata $metadata,
        Keyword $keyword,
        int $index,
    ) {
        $this->metadata = $metadata;
        $this->keyword = $keyword;
        $this->index = $index;

        $this->metadata->getKeywords(raw: true)->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getKeyword(): Keyword
    {
        return $this->keyword;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getName(): string
    {
        return $this->keyword->getName();
    }
}
