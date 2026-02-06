<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use Composer\Package\Version\VersionParser;
use Composer\Pcre\Preg;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetadataRepository::class)]
#[ORM\UniqueConstraint(name: 'version_revision_idx', columns: ['version_id', 'revision'])]
class Metadata extends TrackedEntity implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    private int $revision;

    #[ORM\Column]
    private string $packageName;

    #[ORM\Column]
    private string $versionName;

    #[ORM\Column(length: 191)]
    private string $normalizedVersionName;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $readme = null;

    #[ORM\Column(nullable: true)]
    private ?string $homepage = null;

    #[ORM\Column(nullable: true)]
    private ?array $license = null;

    #[ORM\Column(nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?string $targetDir = null;

    #[ORM\Column(nullable: true)]
    private ?array $source = null;

    #[ORM\Column(nullable: true)]
    private ?array $dist = null;

    #[ORM\Column(nullable: true)]
    private ?array $autoload = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $binaries = null;

    /**
     * @var string[]|null
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

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\ManyToOne(targetEntity: Version::class, inversedBy: 'metadata')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Version $version;

    #[ORM\ManyToOne(targetEntity: Package::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Package $package;

    /**
     * @var Collection<int, MetadataRequireLink>
     */
    #[ORM\OneToMany(targetEntity: MetadataRequireLink::class, mappedBy: 'metadata', cascade: ['persist', 'detach', 'remove'])]
    #[ORM\OrderBy(['index' => 'ASC'])]
    private Collection $require;

    /**
     * @var Collection<int, MetadataDevRequireLink>
     */
    #[ORM\OneToMany(targetEntity: MetadataDevRequireLink::class, mappedBy: 'metadata', cascade: ['persist', 'detach', 'remove'])]
    #[ORM\OrderBy(['index' => 'ASC'])]
    private Collection $devRequire;

    /**
     * @var Collection<int, MetadataConflictLink>
     */
    #[ORM\OneToMany(targetEntity: MetadataConflictLink::class, mappedBy: 'metadata', cascade: ['persist', 'detach', 'remove'])]
    #[ORM\OrderBy(['index' => 'ASC'])]
    private Collection $conflict;

    /**
     * @var Collection<int, MetadataProvideLink>
     */
    #[ORM\OneToMany(targetEntity: MetadataProvideLink::class, mappedBy: 'metadata', cascade: ['persist', 'detach', 'remove'])]
    #[ORM\OrderBy(['index' => 'ASC'])]
    private Collection $provide;

    /**
     * @var Collection<int, MetadataReplaceLink>
     */
    #[ORM\OneToMany(targetEntity: MetadataReplaceLink::class, mappedBy: 'metadata', cascade: ['persist', 'detach', 'remove'])]
    #[ORM\OrderBy(['index' => 'ASC'])]
    private Collection $replace;

    /**
     * @var Collection<int, MetadataSuggestLink>
     */
    #[ORM\OneToMany(targetEntity: MetadataSuggestLink::class, mappedBy: 'metadata', cascade: ['persist', 'detach', 'remove'])]
    #[ORM\OrderBy(['index' => 'ASC'])]
    private Collection $suggest;

    /**
     * @var Collection<int, Keyword>
     */
    #[ORM\ManyToMany(targetEntity: Keyword::class, cascade: ['persist', 'detach', 'remove'])]
    private Collection $keywords;

    public function __construct(Version $version)
    {
        $this->version = $version;
        $this->package = $version->getPackage();

        $this->require = new ArrayCollection();
        $this->devRequire = new ArrayCollection();
        $this->conflict = new ArrayCollection();
        $this->provide = new ArrayCollection();
        $this->replace = new ArrayCollection();
        $this->suggest = new ArrayCollection();
        $this->keywords = new ArrayCollection();
    }

    public function __toString(): string
    {
        return "$this->packageName $this->versionName ($this->normalizedVersionName)";
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function setRevision(int $revision): void
    {
        $this->revision = $revision;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function setPackageName(string $packageName): void
    {
        $this->packageName = $packageName;
    }

    public function getVersionName(): string
    {
        return $this->versionName;
    }

    public function setVersionName(string $versionName): void
    {
        $this->versionName = $versionName;
    }

    public function getNormalizedVersionName(): string
    {
        return $this->normalizedVersionName;
    }

    public function setNormalizedVersionName(string $normalizedVersionName): void
    {
        $this->normalizedVersionName = $normalizedVersionName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getReadme(): ?string
    {
        return $this->readme;
    }

    public function setReadme(?string $readme): void
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

    /**
     * @return string[]|null
     */
    public function getLicense(): ?array
    {
        return $this->license;
    }

    /**
     * @param string[]|null $license
     */
    public function setLicense(?array $license): void
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

    public function getAutoload(): ?array
    {
        return $this->autoload;
    }

    public function setAutoload(?array $autoload): void
    {
        $this->autoload = $autoload;
    }

    /**
     * @return string[]|null
     */
    public function getBinaries(): ?array
    {
        return $this->binaries;
    }

    /**
     * @param string[]|null $binaries
     */
    public function setBinaries(?array $binaries): void
    {
        $this->binaries = $binaries;
    }

    /**
     * @return string[]|null
     */
    public function getIncludePaths(): ?array
    {
        return $this->includePaths;
    }

    /**
     * @param string[]|null $paths
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
        return $this->authors ?? [];
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

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): void
    {
        $this->releasedAt = $releasedAt;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }

    /**
     * @return Collection<int, MetadataRequireLink>
     */
    public function getRequire(): Collection
    {
        return $this->require;
    }

    /**
     * @return Collection<int, MetadataDevRequireLink>
     */
    public function getDevRequire(): Collection
    {
        return $this->devRequire;
    }

    /**
     * @return Collection<int, MetadataConflictLink>
     */
    public function getConflict(): Collection
    {
        return $this->conflict;
    }

    /**
     * @return Collection<int, MetadataProvideLink>
     */
    public function getProvide(): Collection
    {
        return $this->provide;
    }

    /**
     * @return Collection<int, MetadataReplaceLink>
     */
    public function getReplace(): Collection
    {
        return $this->replace;
    }

    /**
     * @return Collection<int, MetadataSuggestLink>
     */
    public function getSuggest(): Collection
    {
        return $this->suggest;
    }

    /**
     * @return Collection<int, Keyword>
     */
    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function hasSource(): bool
    {
        return null !== $this->source;
    }

    public function getSourceReference(): ?string
    {
        return $this->source['reference'] ?? null;
    }

    public function getSourceType(): ?string
    {
        return $this->source['type'] ?? null;
    }

    public function getSourceUrl(): ?string
    {
        return $this->source['url'] ?? null;
    }

    public function hasDist(): bool
    {
        return null !== $this->dist;
    }

    public function getDistReference(): ?string
    {
        return $this->dist['reference'] ?? null;
    }

    public function getDistType(): ?string
    {
        return $this->dist['type'] ?? null;
    }

    public function getDistUrl(): ?string
    {
        return $this->dist['url'] ?? null;
    }

    public function hasVersionAlias(): bool
    {
        return $this->version->isDevelopment() && $this->getVersionAlias();
    }

    public function getVersionAlias(): string
    {
        if (null !== $alias = $this->extra['branch-alias'][$this->versionName] ?? null) {
            $alias = (new VersionParser())->normalizeBranch(str_replace('-dev', '', $alias));
            $alias = Preg::replace('{(\.9{7})+}', '.x', $alias);

            return $alias;
        }

        return '';
    }

    /**
     * Get authors, sorted to help the V2 metadata compression algo.
     *
     * @return array<array{name?: string, email?: string, homepage?: string, role?: string}>|null
     */
    public function getAuthorsSorted(): ?array
    {
        if (null === $this->authors) {
            return null;
        }

        $authors = $this->authors;
        foreach ($authors as &$author) {
            usort($author, static function ($a, $b) {
                static $order = ['name' => 1, 'email' => 2, 'homepage' => 3, 'role' => 4];
                $indexA = $order[$a] ?? 5;
                $indexB = $order[$b] ?? 5;

                // Sort by order, or alphabetically if the fields are not pre-defined
                return $indexA !== $indexB ? $indexA <=> $indexB : $a <=> $b;
            });
        }

        return $authors;
    }

    /**
     * Get funding, sorted to help the V2 metadata compression algo.
     *
     * @return array<array{type?: string, url?: string}>|null
     */
    public function getFundingSorted(): ?array
    {
        if (null === $this->funding) {
            return null;
        }

        $funding = $this->funding;
        usort($funding, static function ($a, $b) {
            $keyA = ($a['type'] ?? '') . ($a['url'] ?? '');
            $keyB = ($b['type'] ?? '') . ($b['url'] ?? '');

            return $keyA <=> $keyB;
        });

        return $funding;
    }

    public function getKeywordNames(): array
    {
        $names = [];
        foreach ($this->keywords as $keyword) {
            $names[] = $keyword->getName();
        }

        return $names;
    }

    public function getBrowsableRepositoryUrl(): ?string
    {
        $reference = $this->getSourceReference();
        $url = $this->package->getBrowsableRepositoryUrl();
        if (null === $reference || null === $url) {
            return null;
        }

        if (str_starts_with($url, 'https://github.com/')) {
            return "$url/tree/$reference";
        } elseif (str_starts_with($url, 'https://gitlab.com/')) {
            return "$url/-/tree/$reference";
        } elseif (str_starts_with($url, 'https://bitbucket.org/')) {
            return "$url/src/$reference/";
        }

        return null;
    }

    public function toComposerArray(): array
    {
        // Set default fields
        $data = [
            'name' => $this->packageName,
            'description' => (string) $this->description,
            'keywords' => $this->getKeywordNames(),
            'homepage' => (string) $this->homepage,
            'version' => $this->versionName,
            'version_normalized' => $this->normalizedVersionName,
            'license' => $this->license,
            'authors' => $this->getAuthorsSorted(),
            'source' => $this->source,
            'dist' => $this->dist,
            'type' => $this->type,
            'autoload' => $this->autoload,
        ];

        // Set links
        $supportedLinkTypes = [
            'require' => $this->require,
            'require-dev' => $this->devRequire,
            'suggest' => $this->suggest,
            'conflict' => $this->conflict,
            'provide' => $this->provide,
            'replace' => $this->replace,
        ];

        foreach ($supportedLinkTypes as $linkType => $linkCollection) {
            /** @var AbstractMetadataLink $link */
            foreach ($linkCollection as $link) {
                $data[$linkType][$link->getLinkedPackageName()] = $link->getLinkedVersionConstraint();
            }
        }

        // Set optional fields
        if (null !== $this->support) {
            $data['support'] = $this->support;
            ksort($data['support']);
        }
        if (null !== $phpExt = $this->phpExt) {
            if (isset($phpExt['configure-options'])) {
                usort($phpExt['configure-options'], static fn ($a, $b) => ($a['name'] ?? '') <=> ($b['name'] ?? ''));
            }

            $data['php-ext'] = $phpExt;
        }
        if (null !== $funding = $this->getFundingSorted()) {
            $data['funding'] = $funding;
        }
        if (null !== $this->releasedAt) {
            $data['time'] = $this->releasedAt->format('Y-m-d\TH:i:sP');
        }
        if (null !== $this->extra) {
            $data['extra'] = $this->extra;
        }
        if (null !== $this->targetDir) {
            $data['target-dir'] = $this->targetDir;
        }
        if (null !== $this->includePaths) {
            $data['include-path'] = $this->includePaths;
        }
        if (null !== $this->binaries) {
            $data['bin'] = $this->binaries;
        }

        // Set administrative fields
        if ($this->getVersion()->isDefaultBranch()) {
            $data['default-branch'] = true;
        }
        if ($this->getPackage()->isAbandoned()) {
            $data['abandoned'] = $this->getPackage()->getReplacementPackage() ?: true;
        }

        return $data;
    }
}
