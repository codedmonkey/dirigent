<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VersionRepository::class)]
#[ORM\UniqueConstraint(name: 'package_version_idx', columns: ['package_id', 'normalized_name'])]
class Version extends TrackedEntity implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(length: 191)]
    private string $normalizedName;

    #[ORM\Column]
    private bool $development;

    #[ORM\Column]
    private bool $defaultBranch = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Package::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Package $package;

    #[ORM\OneToOne]
    private ?Metadata $currentMetadata = null;

    #[ORM\OneToOne(mappedBy: 'version', cascade: ['persist', 'detach', 'remove'])]
    private VersionInstallations $installations;

    #[ORM\OneToMany(targetEntity: Metadata::class, mappedBy: 'version', cascade: ['persist', 'detach', 'remove'])]
    private Collection $metadata;

    public function __construct(Package $package)
    {
        $this->package = $package;

        $this->installations = new VersionInstallations($this);
        $this->metadata = new ArrayCollection();
    }

    public function __toString(): string
    {
        $packageName = $this->package->getName();

        return "$packageName $this->name ($this->normalizedName)";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    public function setNormalizedName(string $normalizedName): void
    {
        $this->normalizedName = $normalizedName;
    }

    public function isDevelopment(): bool
    {
        return $this->development;
    }

    public function setDevelopment(bool $development): void
    {
        $this->development = $development;
    }

    public function isDefaultBranch(): bool
    {
        return $this->defaultBranch;
    }

    public function setDefaultBranch(bool $defaultBranch): void
    {
        $this->defaultBranch = $defaultBranch;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function getCurrentMetadata(): ?Metadata
    {
        return $this->currentMetadata;
    }

    public function setCurrentMetadata(Metadata $metadata): void
    {
        $this->currentMetadata = $metadata;
    }

    public function getInstallations(): VersionInstallations
    {
        return $this->installations;
    }

    /**
     * @return Collection<int, Metadata>
     */
    public function getMetadata(): Collection
    {
        return $this->metadata;
    }

    public function getExtendedName(): string
    {
        return $this->name . ($this->getCurrentMetadata()->hasVersionAlias() ? ' / ' . $this->getCurrentMetadata()->getVersionAlias() : '');
    }

    public function getMajorVersion(): int
    {
        $split = explode('.', $this->name);

        return (int) $split[0];
    }

    public function getMinorVersion(): int
    {
        $split = explode('.', $this->name);

        return (int) $split[1];
    }

    public function getPatchVersion(): int
    {
        $split = explode('.', $this->name);

        return (int) $split[2];
    }
}
