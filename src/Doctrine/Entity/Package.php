<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Composer\HttpDownloaderOptionsFactory;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Repository\Vcs\GitHubDriver;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use Composer\Util\HttpDownloader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity(repositoryClass: PackageRepository::class)]
class Package
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * Unique package name
     */
    #[ORM\Column(length: 191)]
    private string $name;

    #[ORM\Column(length: 191)]
    private string $vendor;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

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

    #[ManyToOne]
    private ?Credentials $repositoryCredentials = null;

    #[ORM\Column(nullable: true)]
    private ?string $remoteId = null;

    /**
     * @var Collection<int, Version>
     */
    #[ORM\OneToMany(mappedBy: 'package', targetEntity: Version::class)]
    private Collection $versions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updateScheduledAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dumpedAt = null;

    #[ORM\ManyToOne]
    private ?Registry $mirrorRegistry = null;

    /**
     * @internal
     * @var VcsDriverInterface|true|null
     */
    public VcsDriverInterface|bool|null $vcsDriver = true;

    /**
     * @internal
     */
    public ?string $vcsDriverError = null;

    /**
     * @var array<string, Version>|null lookup table for versions
     */
    private array|null $cachedVersions = null;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->createdAt = new \DateTime();
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
        $this->vendor = Preg::replace('{/.*$}', '', $this->name);
    }

    /**
     * Get vendor prefix
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * Get package name without vendor
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

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    public function setRepositoryUrl(string $repoUrl): void
    {
        $this->vcsDriver = null;

        // prevent local filesystem URLs
        if (Preg::isMatch('{^(\.|[a-z]:|/)}i', $repoUrl)) {
            return;
        }

        $repoUrl = Preg::replace('{^git@github.com:}i', 'https://github.com/', $repoUrl);
        $repoUrl = Preg::replace('{^git://github.com/}i', 'https://github.com/', $repoUrl);
        $repoUrl = Preg::replace('{^(https://github.com/.*?)\.git$}i', '$1', $repoUrl);
        $repoUrl = Preg::replace('{^(https://github.com/.*?)/$}i', '$1', $repoUrl);

        $repoUrl = Preg::replace('{^git@gitlab.com:}i', 'https://gitlab.com/', $repoUrl);
        $repoUrl = Preg::replace('{^https?://(?:www\.)?gitlab\.com/(.*?)\.git$}i', 'https://gitlab.com/$1', $repoUrl);

        $repoUrl = Preg::replace('{^git@+bitbucket.org:}i', 'https://bitbucket.org/', $repoUrl);
        $repoUrl = Preg::replace('{^bitbucket.org:}i', 'https://bitbucket.org/', $repoUrl);
        $repoUrl = Preg::replace('{^https://[a-z0-9_-]*@bitbucket.org/}i', 'https://bitbucket.org/', $repoUrl);
        $repoUrl = Preg::replace('{^(https://bitbucket.org/[^/]+/[^/]+)/src/[^.]+}i', '$1.git', $repoUrl);

        // normalize protocol case
        $repoUrl = Preg::replaceCallbackStrictGroups('{^(https?|git|svn)://}i', static fn ($match) => strtolower($match[1]) . '://', $repoUrl);

        $this->repositoryUrl = $repoUrl;
        $this->remoteId = null;

        // avoid user@host URLs
        if (Preg::isMatch('{https?://.+@}', $repoUrl)) {
            return;
        }

        // validate that this is a somewhat valid URL
        if (!Preg::isMatch('{^([a-z0-9][^@\s]+@[a-z0-9-_.]+:\S+ | [a-z0-9]+://\S+)$}Dx', $repoUrl)) {
            return;
        }

        // block env vars & ~ prefixes
        if (Preg::isMatch('{^[%$~]}', $repoUrl)) {
            return;
        }

        try {
            $io = new NullIO();
            $config = Factory::createConfig();

            if ($this->repositoryCredentials?->getType() === CredentialsType::GitlabOauth) {
                $config->merge([
                    'config' => [
                        'gitlab-oauth' => [
                            parse_url($this->repositoryUrl, PHP_URL_HOST) => [
                                'token' => $this->repositoryCredentials->getPassword(),
                            ],
                        ],
                    ],
                ]);
            }

            $io->loadConfiguration($config);
            $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());
            $repository = new VcsRepository(['url' => $this->repositoryUrl], $io, $config, $httpDownloader);

            $driver = $this->vcsDriver = $repository->getDriver();
            if (!$driver) {
                return;
            }
            $information = $driver->getComposerInformation($driver->getRootIdentifier());
            if (!isset($information['name']) || !is_string($information['name'])) {
                return;
            }
            if (!isset($this->name)) {
                $this->setName(trim($information['name']));
            }
            if ($driver instanceof GitHubDriver) {
                $this->repositoryUrl = $driver->getRepositoryUrl();
                if ($repoData = $driver->getRepoData()) {
                    $this->remoteId = parse_url($this->repositoryUrl, PHP_URL_HOST).'/'.$repoData['id'];
                }
            }
        } catch (\Exception $e) {
            $this->vcsDriverError = '['.get_class($e).'] '.$e->getMessage();
        }
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

    /**
     * @return Collection<int, Version>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function getVersion(string $normalizedVersion): Version|null
    {
        if (null === $this->cachedVersions) {
            $this->cachedVersions = [];
            foreach ($this->getVersions() as $version) {
                $this->cachedVersions[strtolower($version->getNormalizedVersion())] = $version;
            }
        }

        if (isset($this->cachedVersions[strtolower($normalizedVersion)])) {
            return $this->cachedVersions[strtolower($normalizedVersion)];
        }

        return null;
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

    public function getUpdateScheduledAt(): ?\DateTimeInterface
    {
        return $this->updateScheduledAt;
    }

    public function setUpdateScheduledAt(?\DateTimeInterface $updateScheduledAt): void
    {
        $this->updateScheduledAt = $updateScheduledAt;
    }

    public function getDumpedAt(): ?\DateTimeInterface
    {
        return $this->dumpedAt;
    }

    public function setDumpedAt(?\DateTimeInterface $dumpedAt): void
    {
        $this->dumpedAt = $dumpedAt;
    }

    public function getMirrorRegistry(): ?Registry
    {
        return $this->mirrorRegistry;
    }

    public function setMirrorRegistry(?Registry $mirrorRegistry): void
    {
        $this->mirrorRegistry = $mirrorRegistry;
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
        if ($aVersion === '0.0.0-alpha' && $a->isDefaultBranch()) {
            return -1;
        }
        if ($bVersion === '0.0.0-alpha' && $b->isDefaultBranch()) {
            return 1;
        }

        // equal versions are sorted by date
        if ($aVersion === $bVersion) {
            // make sure sort is stable
            if ($a->getReleasedAt() == $b->getReleasedAt()) {
                return $a->getNormalizedVersion() <=> $b->getNormalizedVersion();
            }

            return $b->getReleasedAt() > $a->getReleasedAt() ? 1 : -1;
        }

        // the rest is sorted by version
        return version_compare($bVersion, $aVersion);
    }
}
