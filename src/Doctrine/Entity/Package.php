<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Validator\UniquePackage;
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
        if (!$repoUrl) {
            $this->repositoryUrl = null;

            return;
        }

        // Force GitHub repos to use standardized format
        $repoUrl = Preg::replace('{^git@github.com:}i', 'https://github.com/', $repoUrl);
        $repoUrl = Preg::replace('{^git://github.com/}i', 'https://github.com/', $repoUrl);
        $repoUrl = Preg::replace('{^(https://github.com/.*?)\.git$}i', '$1', $repoUrl);
        $repoUrl = Preg::replace('{^(https://github.com/.*?)/$}i', '$1', $repoUrl);

        // Force GitLab repos to use standardized format
        $repoUrl = Preg::replace('{^git@gitlab.com:}i', 'https://gitlab.com/', $repoUrl);
        $repoUrl = Preg::replace('{^https?://(?:www\.)?gitlab\.com/(.*?)\.git$}i', 'https://gitlab.com/$1', $repoUrl);

        // Force Bitbucket repos to use standardized format
        $repoUrl = Preg::replace('{^git@+bitbucket.org:}i', 'https://bitbucket.org/', $repoUrl);
        $repoUrl = Preg::replace('{^bitbucket.org:}i', 'https://bitbucket.org/', $repoUrl);
        $repoUrl = Preg::replace('{^https://[a-z0-9_-]*@bitbucket.org/}i', 'https://bitbucket.org/', $repoUrl);
        $repoUrl = Preg::replace('{^(https://bitbucket.org/[^/]+/[^/]+)/src/[^.]+}i', '$1.git', $repoUrl);

        // Normalize protocol case
        $repoUrl = Preg::replaceCallbackStrictGroups('{^(https?|git|svn)://}i', static fn ($match) => strtolower($match[1]) . '://', $repoUrl);

        $this->repositoryUrl = $repoUrl;
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
        if (!$this->repositoryUrl) {
            return null;
        }

        if (!Preg::isMatch('{^https?://}i', $this->repositoryUrl)) {
            return null;
        }

        return $this->repositoryUrl;
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
     * Returns the default branch or latest version of the package.
     */
    public function getDefaultVersion(): ?Version
    {
        $versions = $this->versions->toArray();

        if (!count($versions)) {
            return null;
        }

        usort($versions, [static::class, 'sortVersions']);

        $latestVersion = reset($versions);
        foreach ($versions as $version) {
            if ($version->isDefaultBranch()) {
                return $version;
            }
        }

        return $latestVersion;
    }

    /**
     * Returns the latest (numbered) version of the package, or the default version if no versions were found.
     */
    public function getLatestVersion(): ?Version
    {
        $versions = $this->versions->toArray();

        if (!count($versions)) {
            return null;
        }

        usort($versions, [static::class, 'sortVersions']);

        foreach ($versions as $version) {
            if (!$version->isDevelopment()) {
                return $version;
            }
        }

        return $this->getDefaultVersion();
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
