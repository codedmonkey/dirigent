<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VersionRepository::class)]
#[ORM\UniqueConstraint(name: 'package_version_idx', columns: ['package_id', 'normalized_name'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Version extends TrackedEntity
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

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->currentMetadata->getReleasedAt();
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

    public function getPackageName(): string
    {
        return $this->package->getName();
    }

    public function getDescription(): ?string
    {
        return $this->currentMetadata->getDescription();
    }

    public function getReadme(): ?string
    {
        return $this->currentMetadata->getReadme();
    }

    public function getHomepage(): ?string
    {
        return $this->currentMetadata->getHomepage();
    }

    /**
     * @return string[]
     */
    public function getLicense(): array
    {
        return $this->currentMetadata->getLicense();
    }

    public function getType(): ?string
    {
        return $this->currentMetadata->getType();
    }

    public function getTargetDir(): ?string
    {
        return $this->currentMetadata->getTargetDir();
    }

    public function getSource(): ?array
    {
        return $this->currentMetadata->getSource();
    }

    public function getDist(): ?array
    {
        return $this->currentMetadata->getDist();
    }

    /**
     * @return Collection<int, MetadataRequireLink>
     */
    public function getRequire(): Collection
    {
        return $this->currentMetadata->getRequire();
    }

    /**
     * @return Collection<int, MetadataDevRequireLink>
     */
    public function getDevRequire(): Collection
    {
        return $this->currentMetadata->getDevRequire();
    }

    /**
     * @return Collection<int, MetadataConflictLink>
     */
    public function getConflict(): Collection
    {
        return $this->currentMetadata->getConflict();
    }

    /**
     * @return Collection<int, MetadataProvideLink>
     */
    public function getProvide(): Collection
    {
        return $this->currentMetadata->getProvide();
    }

    /**
     * @return Collection<int, MetadataReplaceLink>
     */
    public function getReplace(): Collection
    {
        return $this->currentMetadata->getReplace();
    }

    /**
     * @return Collection<int, MetadataSuggestLink>
     */
    public function getSuggest(): Collection
    {
        return $this->currentMetadata->getSuggest();
    }

    /**
     * @return Collection<int, Keyword>
     */
    public function getKeywords(): Collection
    {
        return $this->currentMetadata->getKeywords();
    }

    public function getAutoload(): array
    {
        return $this->currentMetadata->getAutoload();
    }

    /**
     * @return string[]|null
     */
    public function getBinaries(): ?array
    {
        return $this->currentMetadata->getBinaries();
    }

    /**
     * @return string[]|null
     */
    public function getIncludePaths(): ?array
    {
        return $this->currentMetadata->getIncludePaths();
    }

    public function getPhpExt(): ?array
    {
        return $this->currentMetadata->getPhpExt();
    }

    public function getAuthors(): array
    {
        return $this->currentMetadata->getAuthors();
    }

    public function getSupport(): ?array
    {
        return $this->currentMetadata->getSupport();
    }

    public function getFunding(): ?array
    {
        return $this->currentMetadata->getFunding();
    }

    /**
     * @return array<mixed>|null
     */
    public function getExtra(): ?array
    {
        return $this->currentMetadata->getExtra();
    }

    public function hasSource(): bool
    {
        return $this->currentMetadata->hasSource();
    }

    public function getSourceReference(): ?string
    {
        return $this->currentMetadata->getSourceReference();
    }

    public function getSourceType(): ?string
    {
        return $this->currentMetadata->getSourceType();
    }

    public function getSourceUrl(): ?string
    {
        return $this->currentMetadata->getSourceUrl();
    }

    public function hasDist(): bool
    {
        return $this->currentMetadata->hasDist();
    }

    public function getDistReference(): ?string
    {
        return $this->currentMetadata->getDistReference();
    }

    public function getDistType(): ?string
    {
        return $this->currentMetadata->getDistType();
    }

    public function getDistUrl(): ?string
    {
        return $this->currentMetadata->getDistUrl();
    }

    public function hasVersionAlias(): bool
    {
        return $this->currentMetadata->hasVersionAlias();
    }

    public function getVersionAlias(): string
    {
        return $this->currentMetadata->getVersionAlias();
    }

    public function getVersionTitle(): string
    {
        return $this->name . ($this->hasVersionAlias() ? ' / ' . $this->getVersionAlias() : '');
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

    public function getBrowsableRepositoryUrl(): ?string
    {
        return $this->currentMetadata->getBrowsableRepositoryUrl();
    }
}
