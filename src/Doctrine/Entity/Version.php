<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VersionRepository::class)]
class Version
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $version;

    #[ORM\Column(length: 191)]
    private string $normalizedVersion;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $readme = null;

    #[ORM\Column(nullable: true)]
    private ?string $homepage = null;

    #[ORM\Column]
    private bool $development;

    #[ORM\Column]
    private array $license;

    #[ORM\Column(nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?string $targetDir = null;

    #[ORM\Column(nullable: true)]
    private ?array $source = null;

    #[ORM\Column(nullable: true)]
    private ?array $dist = null;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: RequireLink::class, cascade: ['persist', 'detach'])]
    private Collection $require;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: DevRequireLink::class, cascade: ['persist', 'detach'])]
    private Collection $devRequire;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: ConflictLink::class, cascade: ['persist', 'detach'])]
    private Collection $conflict;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: ProvideLink::class, cascade: ['persist', 'detach'])]
    private Collection $provide;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: ReplaceLink::class, cascade: ['persist', 'detach'])]
    private Collection $replace;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: SuggestLink::class, cascade: ['persist', 'detach'])]
    private Collection $suggest;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'versions', cascade: ['persist', 'detach'])]
    private Collection $tags;

    #[ORM\Column]
    private array $autoload;

    /**
     * @var array<string>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $binaries = null;

    /**
     * @var array<string>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $includePaths = null;

    #[ORM\Column(nullable: true)]
    private ?array $phpExt = null;

    #[ORM\Column(nullable: true)]
    private ?array $authors = null;

    #[ORM\Column(nullable: true)]
    private ?array $support = null;

    #[ORM\Column(nullable: true)]
    private ?array $funding = null;

    #[ORM\Column(nullable: true)]
    private ?array $extra = null;

    #[ORM\Column]
    private bool $defaultBranch = false;

    #[ORM\ManyToOne(targetEntity: Package::class, inversedBy: 'versions')]
    private ?Package $package;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $releasedAt = null;

    public function __construct()
    {
        $this->require = new ArrayCollection();
        $this->devRequire = new ArrayCollection();
        $this->conflict = new ArrayCollection();
        $this->provide = new ArrayCollection();
        $this->replace = new ArrayCollection();
        $this->suggest = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
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

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getNormalizedVersion(): string
    {
        return $this->normalizedVersion;
    }

    public function setNormalizedVersion(string $normalizedVersion): void
    {
        $this->normalizedVersion = $normalizedVersion;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getReadme(): string
    {
        return (string) $this->readme;
    }

    public function setReadme(string $readme): void
    {
        $this->readme = $readme;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): void
    {
        $this->homepage = $homepage;
    }

    public function isDevelopment(): bool
    {
        return $this->development;
    }

    public function setDevelopment(bool $development): void
    {
        $this->development = $development;
    }

    /**
     * @return array<string>
     */
    public function getLicense(): array
    {
        return $this->license;
    }

    /**
     * @param array<string> $license
     */
    public function setLicense(array $license): void
    {
        $this->license = $license;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getTargetDir(): ?string
    {
        return $this->targetDir;
    }

    public function setTargetDir(?string $targetDir): void
    {
        $this->targetDir = $targetDir;
    }

    public function getSource(): ?array
    {
        return $this->source;
    }

    public function setSource(?array $source): void
    {
        $this->source = $source;
    }

    public function getDist(): ?array
    {
        return $this->dist;
    }

    public function setDist(?array $dist): void
    {
        $this->dist = $dist;
    }

    /**
     * @return Collection<int, RequireLink>
     */
    public function getRequire(): Collection
    {
        return $this->require;
    }

    public function addRequireLink(RequireLink $require): void
    {
        $this->require[] = $require;
    }

    /**
     * @return Collection<int, DevRequireLink>
     */
    public function getDevRequire(): Collection
    {
        return $this->devRequire;
    }

    public function addDevRequireLink(DevRequireLink $devRequire): void
    {
        $this->devRequire[] = $devRequire;
    }

    /**
     * @return Collection<int, ConflictLink>
     */
    public function getConflict(): Collection
    {
        return $this->conflict;
    }

    public function addConflictLink(ConflictLink $conflict): void
    {
        $this->conflict[] = $conflict;
    }

    /**
     * @return Collection<int, ProvideLink>
     */
    public function getProvide(): Collection
    {
        return $this->provide;
    }

    public function addProvideLink(ProvideLink $provide): void
    {
        $this->provide[] = $provide;
    }

    /**
     * @return Collection<int, ReplaceLink>
     */
    public function getReplace(): Collection
    {
        return $this->replace;
    }

    public function addReplaceLink(ReplaceLink $replace): void
    {
        $this->replace[] = $replace;
    }

    /**
     * @return Collection<int, SuggestLink>
     */
    public function getSuggest(): Collection
    {
        return $this->suggest;
    }

    public function addSuggestLink(SuggestLink $suggest): void
    {
        $this->suggest[] = $suggest;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        $this->tags[] = $tag;
    }

    public function getAutoload(): array
    {
        return $this->autoload;
    }

    public function setAutoload(array $autoload): void
    {
        $this->autoload = $autoload;
    }

    /**
     * @return array<string>|null
     */
    public function getBinaries(): ?array
    {
        return $this->binaries;
    }

    /**
     * @param array<string>|null $binaries
     */
    public function setBinaries(?array $binaries): void
    {
        $this->binaries = $binaries;
    }

    /**
     * @return array<string>|null
     */
    public function getIncludePaths(): ?array
    {
        return $this->includePaths;
    }

    /**
     * @param array<string>|null $paths
     */
    public function setIncludePaths(?array $paths): void
    {
        $this->includePaths = $paths;
    }

    public function getPhpExt(): ?array
    {
        return $this->phpExt;
    }

    public function setPhpExt(?array $phpExt): void
    {
        $this->phpExt = $phpExt;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function setAuthors(array $authors): void
    {
        $this->authors = $authors;
    }

    public function getSupport(): ?array
    {
        return $this->support;
    }

    public function setSupport(?array $support): void
    {
        $this->support = $support;
    }

    public function getFunding(): ?array
    {
        return $this->funding;
    }

    public function setFunding(?array $funding): void
    {
        $this->funding = $funding;
    }

    /**
     * @return array<mixed>|null
     */
    public function getExtra(): ?array
    {
        return $this->extra;
    }

    /**
     * @param array<mixed>|null $extra
     */
    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }

    public function isDefaultBranch(): bool
    {
        return $this->defaultBranch;
    }

    public function setDefaultBranch(bool $defaultBranch): void
    {
        $this->defaultBranch = $defaultBranch;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): void
    {
        $this->package = $package;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getReleasedAt(): ?\DateTimeInterface
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeInterface $releasedAt): void
    {
        $this->releasedAt = $releasedAt;
    }
}
