<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use CodedMonkey\Dirigent\Validator\UniquePackage;
use Composer\Package\Version\VersionParser;
use Composer\Pcre\Preg;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PackageRepository::class)]
#[ORM\UniqueConstraint(name: 'package_name_idx', columns: ['name'])]
#[UniquePackage]
class Package extends TrackedEntity
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * Unique package name.
     */
    #[ORM\Column(length: 191)]
    private ?string $name = null;

    #[ORM\Column(length: 191)]
    private string $vendor;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?string $language = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $readme = null;

    #[ORM\Column]
    private bool $abandoned = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $replacementPackage = null;

    #[ORM\Column(nullable: true)]
    private ?string $repositoryType = null;

    #[ORM\Column(nullable: true)]
    private ?string $repositoryUrl = null;

    #[ORM\ManyToOne]
    private ?Credentials $repositoryCredentials = null;

    #[ORM\Column(nullable: true)]
    private ?string $remoteId = null;

    #[ORM\Column(nullable: true, enumType: PackageFetchStrategy::class)]
    private PackageFetchStrategy|string|null $fetchStrategy = null;

    #[ORM\ManyToOne]
    private ?Registry $mirrorRegistry = null;

    #[ORM\OneToOne(mappedBy: 'package', cascade: ['persist', 'detach', 'remove'])]
    private PackageInstallations $installations;

    /**
     * @var Collection<int, Version>
     */
    #[ORM\OneToMany(targetEntity: Version::class, mappedBy: 'package', cascade: ['remove'])]
    private Collection $versions;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updateScheduledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dumpedAt = null;

    /**
     * @var array<string, Version> lookup table for versions
     */
    private array $cachedVersions;

    private array $sortedVersions;

    public function __construct()
    {
        $this->installations = new PackageInstallations($this);
        $this->versions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->vendor = Preg::replace('{/.*$}', '', $this->name);
    }

    /**
     * Get vendor prefix.
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * Get package name without vendor.
     */
    public function getPackageName(): string
    {
        return Preg::replace('{^[^/]*/}', '', $this->name);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getReadme(): string
    {
        return (string) $this->readme;
    }

    public function setReadme(string $readme): void
    {
        $this->readme = $readme;
    }

    public function isAbandoned(): bool
    {
        return $this->abandoned;
    }

    public function setAbandoned(bool $abandoned): void
    {
        $this->abandoned = $abandoned;
    }

    public function getReplacementPackage(): ?string
    {
        return $this->replacementPackage;
    }

    public function setReplacementPackage(?string $replacementPackage): void
    {
        if ('' === $replacementPackage) {
            $this->replacementPackage = null;
        } else {
            $this->replacementPackage = $replacementPackage;
        }
    }

    public function getRepositoryType(): ?string
    {
        return $this->repositoryType;
    }

    public function setRepositoryType(string $repositoryType): void
    {
        $this->repositoryType = $repositoryType;
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    public function setRepositoryUrl(?string $repoUrl): void
    {
        $this->repositoryUrl = PackageMetadataResolver::optimizeRepositoryUrl($repoUrl);
    }

    public function getRepositoryCredentials(): ?Credentials
    {
        return $this->repositoryCredentials;
    }

    public function setRepositoryCredentials(?Credentials $repositoryCredentials): void
    {
        $this->repositoryCredentials = $repositoryCredentials;
    }

    public function getRemoteId(): ?string
    {
        return $this->remoteId;
    }

    public function setRemoteId(?string $remoteId): void
    {
        $this->remoteId = $remoteId;
    }

    public function getFetchStrategy(): PackageFetchStrategy|string
    {
        if (!$this->fetchStrategy) {
            return $this->mirrorRegistry ? PackageFetchStrategy::Mirror : PackageFetchStrategy::Vcs;
        }

        return $this->fetchStrategy;
    }

    public function setFetchStrategy(PackageFetchStrategy|string $fetchStrategy): void
    {
        $this->fetchStrategy = $fetchStrategy;
    }

    public function getMirrorRegistry(): ?Registry
    {
        return $this->mirrorRegistry;
    }

    public function setMirrorRegistry(?Registry $mirrorRegistry): void
    {
        $this->mirrorRegistry = $mirrorRegistry;
    }

    public function getInstallations(): PackageInstallations
    {
        return $this->installations;
    }

    /**
     * @return Collection<int, Version>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function getVersion(string $normalizedVersion): ?Version
    {
        if (!isset($this->cachedVersions)) {
            $this->cachedVersions = [];
            foreach ($this->getVersions() as $version) {
                $this->cachedVersions[strtolower($version->getNormalizedName())] = $version;
            }
        }

        return $this->cachedVersions[strtolower($normalizedVersion)] ?? null;
    }

    #[\Override]
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isUpdateScheduled(): bool
    {
        return null !== $this->updateScheduledAt;
    }

    public function getUpdateScheduledAt(): ?\DateTimeImmutable
    {
        return $this->updateScheduledAt;
    }

    public function setUpdateScheduledAt(?\DateTimeImmutable $updateScheduledAt): void
    {
        $this->updateScheduledAt = $updateScheduledAt;
    }

    public function getDumpedAt(): ?\DateTimeImmutable
    {
        return $this->dumpedAt;
    }

    public function setDumpedAt(?\DateTimeImmutable $dumpedAt): void
    {
        $this->dumpedAt = $dumpedAt;
    }

    public function getBrowsableRepositoryUrl(): ?string
    {
        if (null === $this->repositoryUrl) {
            return null;
        }

        $url = PackageMetadataResolver::optimizeRepositoryUrl($this->repositoryUrl);

        static $allowedDomains = ['github.com', 'gitlab.com', 'bitbucket.org'];
        foreach ($allowedDomains as $domain) {
            if (str_starts_with((string) $url, "https://$domain/")) {
                return $url;
            }
        }

        return null;
    }

    public function getPrettyBrowsableRepositoryUrl(): ?string
    {
        if (null === $url = $this->getBrowsableRepositoryUrl()) {
            return null;
        }

        $url = preg_replace('#^https?://#', '', $url);

        return $url;
    }

    /**
     * @return Version[]
     */
    public function getSortedVersions(): array
    {
        if (!isset($this->sortedVersions)) {
            $this->sortedVersions = $this->versions->toArray();

            usort($this->sortedVersions, static::sortVersions(...));
        }

        return $this->sortedVersions;
    }

    /**
     * Returns the default branch or the latest version of the package.
     */
    public function getDefaultVersion(): ?Version
    {
        $versions = $this->getSortedVersions();

        if (!count($versions)) {
            return null;
        }

        $latestVersion = reset($versions);
        foreach ($versions as $version) {
            if ($version->isDefaultBranch()) {
                return $version;
            }
        }

        return $latestVersion;
    }

    /**
     * The latest (numbered) version of the package, or the default version if no versions were found.
     */
    public function getLatestVersion(): ?Version
    {
        $versions = $this->getSortedVersions();

        if (!count($versions)) {
            return null;
        }

        // Return the first non-development version
        foreach ($versions as $version) {
            if (!$version->isDevelopment()) {
                return $version;
            }
        }

        return $this->getDefaultVersion();
    }

    /**
     * The latest version of each major version.
     *
     * @return Version[]
     */
    public function getActiveVersions(): array
    {
        $activeVersions = [];
        $activePrereleaseVersions = [];

        foreach ($this->getSortedVersions() as $version) {
            if ('stable' !== VersionParser::parseStability($version->getNormalizedName())) {
                continue;
            }

            [$majorVersion, $minorVersion] = explode('.', $version->getNormalizedName());

            if ('0' === $majorVersion) {
                $prereleaseVersion = "$majorVersion.$minorVersion";

                $activePrereleaseVersions[$prereleaseVersion] ??= $version;
                if (version_compare($version->getNormalizedName(), $activePrereleaseVersions[$prereleaseVersion]->getNormalizedName(), '>')) {
                    $activePrereleaseVersions[$prereleaseVersion] = $version;
                }

                continue;
            }

            $activeVersions[$majorVersion] ??= $version;
            if (version_compare($version->getNormalizedName(), $activeVersions[$majorVersion]->getNormalizedName(), '>')) {
                $activeVersions[$majorVersion] = $version;
            }
        }

        $activeDevelopmentVersions = [];
        $activePrereleaseDevelopmentVersions = [];

        // Find newer unstable releases of active versions
        foreach ($this->getSortedVersions() as $version) {
            if (in_array(VersionParser::parseStability($version->getNormalizedName()), ['stable', 'dev'], true)) {
                continue;
            }

            [$majorVersion, $minorVersion] = explode('.', $version->getNormalizedName());

            $developmentVersion = "$majorVersion.$minorVersion";

            if ('0' === $majorVersion) {
                if (isset($activePrereleaseVersions[$developmentVersion]) && !version_compare($version->getNormalizedName(), $activePrereleaseVersions[$developmentVersion]->getNormalizedName(), '>')) {
                    continue;
                }

                $activePrereleaseDevelopmentVersions[$developmentVersion] ??= $version;
                if (version_compare($version->getNormalizedName(), $activePrereleaseDevelopmentVersions[$developmentVersion]->getNormalizedName(), '>')) {
                    $activePrereleaseDevelopmentVersions[$developmentVersion] = $version;
                }

                continue;
            }

            if (isset($activeVersions[$majorVersion]) && !version_compare($version->getNormalizedName(), $activeVersions[$majorVersion]->getNormalizedName(), '>')) {
                continue;
            }

            $activeDevelopmentVersions[$developmentVersion] ??= $version;
            if (version_compare($version->getNormalizedName(), $activeDevelopmentVersions[$developmentVersion]->getNormalizedName(), '>')) {
                $activeDevelopmentVersions[$version->getNormalizedName()] = $version;
            }
        }

        $activeVersions = [...$activeVersions, ...$activeDevelopmentVersions];

        if (count($activeVersions)) {
            usort($activeVersions, static::sortVersions(...));

            return $activeVersions;
        }

        // Only show pre-release versions (0.x.x) if no versions after 1.0.0 was found
        $activePrereleaseVersions = [...$activePrereleaseVersions, ...$activePrereleaseDevelopmentVersions];

        usort($activePrereleaseVersions, static::sortVersions(...));

        return $activePrereleaseVersions;
    }

    /**
     * All non-development versions that are not part of the active versions.
     *
     * @return Version[]
     */
    public function getHistoricalVersions(): array
    {
        $historicalVersions = array_filter($this->getSortedVersions(), static fn (Version $version) => !$version->isDevelopment());

        return array_diff($historicalVersions, $this->getActiveVersions());
    }

    /**
     * All development versions associated with a version number (2.0.x-dev, 0.1.x-dev).
     *
     * @return Version[]
     */
    public function getDevVersions(): array
    {
        return array_filter($this->getSortedVersions(), static function (Version $version) {
            if (str_ends_with($version->getNormalizedName(), '.9999999-dev')) {
                return true;
            }

            static $parser = new VersionParser();

            return $version->hasVersionAlias() && str_ends_with((string) $parser->normalize($version->getVersionAlias()), '.9999999-dev');
        });
    }

    /**
     * All development versions associated with a branch (dev-main, dev-master, dev-develop).
     *
     * @return Version[]
     */
    public function getDevBranchVersions(): array
    {
        return array_filter($this->getSortedVersions(), static fn (Version $version) => str_starts_with($version->getNormalizedName(), 'dev-'));
    }

    /**
     * Sort versions from newest to oldest.
     */
    public static function sortVersions(Version $a, Version $b): int
    {
        $aName = $a->getNormalizedName();
        $bName = $b->getNormalizedName();

        // Use the branch alias for sorting if one is provided
        if (null !== $aBranchAlias = $a->getExtra()['branch-alias'][$aName] ?? null) {
            $aName = Preg::replace('{(.x)?-dev$}', '.9999999-dev', $aBranchAlias);
        }
        if (null !== $bBranchAlias = $b->getExtra()['branch-alias'][$bName] ?? null) {
            $bName = Preg::replace('{(.x)?-dev$}', '.9999999-dev', $bBranchAlias);
        }

        $aName = Preg::replace('{^dev-.*}', '0.0.0-alpha', $aName);
        $bName = Preg::replace('{^dev-.*}', '0.0.0-alpha', $bName);

        // Sort the default branch first if it is non-numeric
        if ('0.0.0-alpha' === $aName && $a->isDefaultBranch()) {
            return -1;
        }
        if ('0.0.0-alpha' === $bName && $b->isDefaultBranch()) {
            return 1;
        }

        if ($aName !== $bName) {
            return version_compare($bName, $aName);
        }

        // Equal versions are sorted by release date
        $aReleasedAt = $a->getReleasedAt();
        $bReleasedAt = $b->getReleasedAt();

        if (0 !== $sort = $bReleasedAt <=> $aReleasedAt) {
            return $sort;
        }

        // Add a stable fallback sort
        return $b->getId() <=> $a->getId();
    }
}
