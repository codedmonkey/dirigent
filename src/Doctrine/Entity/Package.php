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
    private ?PackageFetchStrategy $fetchStrategy = null;

    #[ORM\Column(enumType: PackageDistributionStrategy::class)]
    private PackageDistributionStrategy $distributionStrategy = PackageDistributionStrategy::Dynamic;

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

    public function getFetchStrategy(): PackageFetchStrategy
    {
        if (null === $this->fetchStrategy) {
            return $this->mirrorRegistry ? PackageFetchStrategy::Mirror : PackageFetchStrategy::Vcs;
        }

        return $this->fetchStrategy;
    }

    public function setFetchStrategy(?PackageFetchStrategy $fetchStrategy): void
    {
        $this->fetchStrategy = $fetchStrategy;
    }

    public function getDistributionStrategy(): PackageDistributionStrategy
    {
        return $this->distributionStrategy;
    }

    public function setDistributionStrategy(PackageDistributionStrategy $distributionStrategy): void
    {
        $this->distributionStrategy = $distributionStrategy;
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
                $this->cachedVersions[strtolower($version->getNormalizedVersion())] = $version;
            }
        }

        return $this->cachedVersions[strtolower($normalizedVersion)] ?? null;
    }

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
            if (str_starts_with($url, "https://$domain/")) {
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

            usort($this->sortedVersions, [static::class, 'sortVersions']);
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
            if ('stable' !== VersionParser::parseStability($version->getNormalizedVersion())) {
                continue;
            }

            [$majorVersion, $minorVersion] = explode('.', $version->getNormalizedVersion());

            if ('0' === $majorVersion) {
                $prereleaseVersion = "$majorVersion.$minorVersion";

                $activePrereleaseVersions[$prereleaseVersion] ??= $version;
                if (version_compare($version->getNormalizedVersion(), $activePrereleaseVersions[$prereleaseVersion]->getNormalizedVersion(), '>')) {
                    $activePrereleaseVersions[$prereleaseVersion] = $version;
                }

                continue;
            }

            $activeVersions[$majorVersion] ??= $version;
            if (version_compare($version->getNormalizedVersion(), $activeVersions[$majorVersion]->getNormalizedVersion(), '>')) {
                $activeVersions[$majorVersion] = $version;
            }
        }

        $activeDevelopmentVersions = [];
        $activePrereleaseDevelopmentVersions = [];

        // Find newer unstable releases of active versions
        foreach ($this->getSortedVersions() as $version) {
            if (in_array(VersionParser::parseStability($version->getNormalizedVersion()), ['stable', 'dev'], true)) {
                continue;
            }

            [$majorVersion, $minorVersion] = explode('.', $version->getNormalizedVersion());

            $developmentVersion = "$majorVersion.$minorVersion";

            if ('0' === $majorVersion) {
                if (isset($activePrereleaseVersions[$developmentVersion]) && !version_compare($version->getNormalizedVersion(), $activePrereleaseVersions[$developmentVersion]->getNormalizedVersion(), '>')) {
                    continue;
                }

                $activePrereleaseDevelopmentVersions[$developmentVersion] ??= $version;
                if (version_compare($version->getNormalizedVersion(), $activePrereleaseDevelopmentVersions[$developmentVersion]->getNormalizedVersion(), '>')) {
                    $activePrereleaseDevelopmentVersions[$developmentVersion] = $version;
                }

                continue;
            }

            if (isset($activeVersions[$majorVersion]) && !version_compare($version->getNormalizedVersion(), $activeVersions[$majorVersion]->getNormalizedVersion(), '>')) {
                continue;
            }

            $activeDevelopmentVersions[$developmentVersion] ??= $version;
            if (version_compare($version->getNormalizedVersion(), $activeDevelopmentVersions[$developmentVersion]->getNormalizedVersion(), '>')) {
                $activeDevelopmentVersions[$version->getNormalizedVersion()] = $version;
            }
        }

        $activeVersions = [...$activeVersions, ...$activeDevelopmentVersions];

        if (count($activeVersions)) {
            usort($activeVersions, [static::class, 'sortVersions']);

            return $activeVersions;
        }

        // Only show pre-release versions (0.x.x) if no versions after 1.0.0 was found
        $activePrereleaseVersions = [...$activePrereleaseVersions, ...$activePrereleaseDevelopmentVersions];

        usort($activePrereleaseVersions, [static::class, 'sortVersions']);

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
            if (str_ends_with($version->getNormalizedVersion(), '.9999999-dev')) {
                return true;
            }

            static $parser = new VersionParser();

            return $version->hasVersionAlias() && str_ends_with($parser->normalize($version->getVersionAlias()), '.9999999-dev');
        });
    }

    /**
     * All development versions associated with a branch (dev-main, dev-master, dev-develop).
     *
     * @return Version[]
     */
    public function getDevBranchVersions(): array
    {
        return array_filter($this->getSortedVersions(), static fn (Version $version) => str_starts_with($version->getNormalizedVersion(), 'dev-'));
    }

    public static function sortVersions(Version $a, Version $b): int
    {
        $aVersion = $a->getNormalizedVersion();
        $bVersion = $b->getNormalizedVersion();

        // use branch alias for sorting if one is provided
        if (isset($a->getExtra()['branch-alias'][$aVersion])) {
            $aVersion = Preg::replace('{(.x)?-dev$}', '.9999999-dev', $a->getExtra()['branch-alias'][$aVersion]);
        }
        if (isset($b->getExtra()['branch-alias'][$bVersion])) {
            $bVersion = Preg::replace('{(.x)?-dev$}', '.9999999-dev', $b->getExtra()['branch-alias'][$bVersion]);
        }

        $aVersion = Preg::replace('{^dev-.*}', '0.0.0-alpha', $aVersion);
        $bVersion = Preg::replace('{^dev-.*}', '0.0.0-alpha', $bVersion);

        // sort default branch first if it is non numeric
        if ('0.0.0-alpha' === $aVersion && $a->isDefaultBranch()) {
            return -1;
        }
        if ('0.0.0-alpha' === $bVersion && $b->isDefaultBranch()) {
            return 1;
        }

        // equal versions are sorted by date
        if ($aVersion === $bVersion) {
            // make sure sort is stable
            if ($a->getReleasedAt() === $b->getReleasedAt()) {
                return $a->getNormalizedVersion() <=> $b->getNormalizedVersion();
            }

            return $b->getReleasedAt() > $a->getReleasedAt() ? 1 : -1;
        }

        // the rest is sorted by version
        return version_compare($bVersion, $aVersion);
    }
}
