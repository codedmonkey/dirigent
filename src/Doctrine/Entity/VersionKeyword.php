<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VersionKeyword
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'keywords')]
    #[ORM\JoinColumn(nullable: false)]
    private Version $version;

    #[ORM\ManyToOne(targetEntity: Keyword::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Keyword $keyword;

    #[ORM\Column]
    private int $index;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function setVersion(Version $version): void
    {
        $this->version = $version;
    }

    public function getKeyword(): Keyword
    {
        return $this->keyword;
    }

    public function setKeyword(Keyword $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setIndex(int $index): void
    {
        $this->index = $index;
    }

    public function getName(): string
    {
        return $this->keyword->getName();
    }
}
