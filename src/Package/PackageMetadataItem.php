<?php

namespace CodedMonkey\Conductor\Package;

class PackageMetadataItem
{
    public array $content = [
        'aliases' => [],
        'versions' => [],
    ];
    public bool $degraded = true;
    public bool $found = false;
    public ?string $lastModified = null;
    public ?string $lastResolved = null;

    public ?bool $fresh = null;

    public function __construct(
        public readonly string $key,
    ) {
    }

    public function isDegraded(): bool
    {
        return true === $this->degraded;
    }

    public function isFound(): bool
    {
        return true === $this->found;
    }

    public function isFresh(): bool
    {
        return true === $this->fresh;
    }

    public function isResolved(): bool
    {
        return null !== $this->lastResolved;
    }

    public function lastResolvedAt(): ?\DateTimeImmutable
    {
        if (null === $this->lastResolved) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $this->lastResolved, new \DateTimeZone('UTC'));
    }
}
