<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use Composer\Package\Package as ComposerPackage;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Pcre\Preg;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VersionRepository::class)]
#[ORM\UniqueConstraint(name: 'pkg_ver_idx', columns: ['package_id', 'normalized_version'])]
class Version extends TrackedEntity
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

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: VersionRequireLink::class, cascade: ['persist', 'detach', 'remove'])]
    private Collection $require;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: VersionDevRequireLink::class, cascade: ['persist', 'detach', 'remove'])]
    private Collection $devRequire;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: VersionConflictLink::class, cascade: ['persist', 'detach', 'remove'])]
    private Collection $conflict;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: VersionProvideLink::class, cascade: ['persist', 'detach', 'remove'])]
    private Collection $provide;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: VersionReplaceLink::class, cascade: ['persist', 'detach', 'remove'])]
    private Collection $replace;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: VersionSuggestLink::class, cascade: ['persist', 'detach', 'remove'])]
    private Collection $suggest;

    #[ORM\ManyToMany(targetEntity: Keyword::class, inversedBy: 'versions', cascade: ['persist', 'detach', 'remove'])]
    private Collection $keywords;

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
    private ?Package $package = null;

    #[ORM\OneToOne(mappedBy: 'version', cascade: ['persist', 'detach', 'remove'])]
    private VersionInstallations $installations;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

    public function __construct()
    {
        $this->require = new ArrayCollection();
        $this->devRequire = new ArrayCollection();
        $this->conflict = new ArrayCollection();
        $this->provide = new ArrayCollection();
        $this->replace = new ArrayCollection();
        $this->suggest = new ArrayCollection();
        $this->keywords = new ArrayCollection();
        $this->installations = new VersionInstallations($this);
    }

    public function __toString(): string
    {
        return "$this->name $this->version ($this->normalizedVersion)";
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
     * @return Collection<int, VersionRequireLink>
     */
    public function getRequire(): Collection
    {
        return $this->require;
    }

    public function addRequireLink(VersionRequireLink $require): void
    {
        $this->require[] = $require;
    }

    /**
     * @return Collection<int, VersionDevRequireLink>
     */
    public function getDevRequire(): Collection
    {
        return $this->devRequire;
    }

    public function addDevRequireLink(VersionDevRequireLink $devRequire): void
    {
        $this->devRequire[] = $devRequire;
    }

    /**
     * @return Collection<int, VersionConflictLink>
     */
    public function getConflict(): Collection
    {
        return $this->conflict;
    }

    public function addConflictLink(VersionConflictLink $conflict): void
    {
        $this->conflict[] = $conflict;
    }

    /**
     * @return Collection<int, VersionProvideLink>
     */
    public function getProvide(): Collection
    {
        return $this->provide;
    }

    public function addProvideLink(VersionProvideLink $provide): void
    {
        $this->provide[] = $provide;
    }

    /**
     * @return Collection<int, VersionReplaceLink>
     */
    public function getReplace(): Collection
    {
        return $this->replace;
    }

    public function addReplaceLink(VersionReplaceLink $replace): void
    {
        $this->replace[] = $replace;
    }

    /**
     * @return Collection<int, VersionSuggestLink>
     */
    public function getSuggest(): Collection
    {
        return $this->suggest;
    }

    public function addSuggestLink(VersionSuggestLink $suggest): void
    {
        $this->suggest[] = $suggest;
    }

    /**
     * @return Collection<int, Keyword>
     */
    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function addKeyword(Keyword $keyword): void
    {
        $this->keywords[] = $keyword;
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

    public function isDefaultBranch(): bool
    {
        return $this->defaultBranch;
    }

    public function setDefaultBranch(bool $defaultBranch): void
    {
        $this->defaultBranch = $defaultBranch;
    }

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): void
    {
        $this->package = $package;
    }

    public function getInstallations(): VersionInstallations
    {
        return $this->installations;
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
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): void
    {
        $this->releasedAt = $releasedAt;
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
        return $this->isDevelopment() && $this->getVersionAlias();
    }

    public function getVersionAlias(): string
    {
        $extra = $this->getExtra();

        if (isset($extra['branch-alias'][$this->getVersion()])) {
            $parser = new VersionParser();
            $version = $parser->normalizeBranch(str_replace('-dev', '', $extra['branch-alias'][$this->getVersion()]));

            return Preg::replace('{(\.9{7})+}', '.x', $version);
        }

        return '';
    }

    public function getVersionTitle(): string
    {
        return $this->version . ($this->hasVersionAlias() ? ' / ' . $this->getVersionAlias() : '');
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

    public function getMajorVersion(): int
    {
        $split = explode('.', $this->version);

        return (int) $split[0];
    }

    public function getMinorVersion(): int
    {
        $split = explode('.', $this->version);

        return (int) $split[1];
    }

    public function getPatchVersion(): int
    {
        $split = explode('.', $this->version);

        return (int) $split[2];
    }

    public function getBrowsableRepositoryUrl(): ?string
    {
        $reference = $this->getSourceReference();
        $url = $this->package->getBrowsableRepositoryUrl();
        if (null === $reference || null === $url) {
            return null;
        }

        if (false === $this->isDevelopment()) {
            $reference = $this->getVersion();
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
        $keywords = [];
        foreach ($this->getKeywords() as $keyword) {
            $keywords[] = $keyword->getName();
        }

        $authors = $this->getAuthors();
        foreach ($authors as &$author) {
            uksort($author, [$this, 'sortAuthorKeys']);
        }
        unset($author);

        $data = [
            'name' => $this->getName(),
            'description' => (string) $this->getDescription(),
            'keywords' => $keywords,
            'homepage' => (string) $this->getHomepage(),
            'version' => $this->getVersion(),
            'version_normalized' => $this->getNormalizedVersion(),
            'license' => $this->getLicense(),
            'authors' => $authors,
            'source' => $this->getSource(),
            'dist' => $this->getDist(),
            'type' => $this->getType(),
        ];

        if ($this->getSupport()) {
            $data['support'] = $this->getSupport();
        }
        if (null !== $this->getPhpExt()) {
            $data['php-ext'] = $this->getPhpExt();
        }
        $funding = $this->getFundingSorted();
        if (null !== $funding) {
            $data['funding'] = $funding;
        }
        if ($this->getReleasedAt()) {
            $data['time'] = $this->getReleasedAt()->format('Y-m-d\TH:i:sP');
        }
        if ($this->getAutoload()) {
            $data['autoload'] = $this->getAutoload();
        }
        if ($this->getExtra()) {
            $data['extra'] = $this->getExtra();
        }
        if ($this->getTargetDir()) {
            $data['target-dir'] = $this->getTargetDir();
        }
        if ($this->getIncludePaths()) {
            $data['include-path'] = $this->getIncludePaths();
        }
        if ($this->getBinaries()) {
            $data['bin'] = $this->getBinaries();
        }

        $supportedLinkTypes = [
            'require' => 'require',
            'devRequire' => 'require-dev',
            'suggest' => 'suggest',
            'conflict' => 'conflict',
            'provide' => 'provide',
            'replace' => 'replace',
        ];

        if ($this->isDefaultBranch()) {
            $data['default-branch'] = true;
        }

        foreach ($supportedLinkTypes as $method => $linkType) {
            /** @var AbstractVersionLink $link */
            foreach ($this->{'get' . $method}() as $link) {
                $data[$linkType][$link->getLinkedPackageName()] = $link->getLinkedVersionConstraint();
            }
        }

        if ($this->getPackage()->isAbandoned()) {
            $data['abandoned'] = $this->getPackage()->getReplacementPackage() ?: true;
        }

        if (isset($data['support'])) {
            ksort($data['support']);
        }

        if (isset($data['php-ext']['configure-options'])) {
            usort($data['php-ext']['configure-options'], fn ($a, $b) => ($a['name'] ?? '') <=> ($b['name'] ?? ''));
        }

        return $data;
    }

    public function toComposerPackage(): PackageInterface
    {
        $composerPackage = new ComposerPackage($this->getName(), $this->getNormalizedVersion(), $this->getVersion());

        $composerPackage->setSourceReference($this->getSourceReference());
        $composerPackage->setSourceUrl($this->getSourceUrl());

        return $composerPackage;
    }

    private function sortAuthorKeys(string $a, string $b): int
    {
        static $order = ['name' => 1, 'email' => 2, 'homepage' => 3, 'role' => 4];
        $aIndex = $order[$a] ?? 5;
        $bIndex = $order[$b] ?? 5;
        if ($aIndex === $bIndex) {
            return $a <=> $b;
        }

        return $aIndex <=> $bIndex;
    }
}
