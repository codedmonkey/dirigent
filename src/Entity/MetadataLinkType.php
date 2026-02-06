<?php

namespace CodedMonkey\Dirigent\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\AbstractMetadataLink;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataConflictLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataDevRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataProvideLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataReplaceLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataSuggestLink;
use Composer\Package\Link as ComposerPackageLink;
use Composer\Package\PackageInterface;
use Doctrine\Common\Collections\Collection;

enum MetadataLinkType: string
{
    case Require = 'require';
    case DevRequire = 'require-dev';
    case Conflict = 'conflict';
    case Provide = 'provide';
    case Replace = 'replace';
    case Suggest = 'suggest';

    public static function fromClass(string $class): self
    {
        return match ($class) {
            MetadataRequireLink::class => self::Require,
            MetadataDevRequireLink::class => self::DevRequire,
            MetadataConflictLink::class => self::Conflict,
            MetadataProvideLink::class => self::Provide,
            MetadataReplaceLink::class => self::Replace,
            MetadataSuggestLink::class => self::Suggest,
            default => throw new \InvalidArgumentException("Invalid class: $class"),
        };
    }

    public function getClass(): string
    {
        return match ($this) {
            self::Require => MetadataRequireLink::class,
            self::DevRequire => MetadataDevRequireLink::class,
            self::Conflict => MetadataConflictLink::class,
            self::Provide => MetadataProvideLink::class,
            self::Replace => MetadataReplaceLink::class,
            self::Suggest => MetadataSuggestLink::class,
        };
    }

    /**
     * @return array<string, ComposerPackageLink>|ComposerPackageLink[]|array<string, string>
     */
    public function getComposerPackageLinks(PackageInterface $package): array
    {
        return match ($this) {
            self::Require => $package->getRequires(),
            self::DevRequire => $package->getDevRequires(),
            self::Conflict => $package->getConflicts(),
            self::Provide => $package->getProvides(),
            self::Replace => $package->getReplaces(),
            self::Suggest => $package->getSuggests(),
        };
    }

    /**
     * @return Collection<int, AbstractMetadataLink>
     */
    public function getMetadataLinks(Metadata $metadata): Collection
    {
        return match ($this) {
            self::Require => $metadata->getRequireLinks(),
            self::DevRequire => $metadata->getDevRequireLinks(),
            self::Conflict => $metadata->getConflictLinks(),
            self::Provide => $metadata->getProvideLinks(),
            self::Replace => $metadata->getReplaceLinks(),
            self::Suggest => $metadata->getSuggestLinks(),
        };
    }

    public function isConstraintLink(): bool
    {
        return self::Suggest !== $this;
    }
}
