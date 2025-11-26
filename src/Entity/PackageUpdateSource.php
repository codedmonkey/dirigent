<?php

namespace CodedMonkey\Dirigent\Entity;

enum PackageUpdateSource: string
{
    case Manual = 'manual';
    case Stale = 'stale';
    case Dynamic = 'dynamic';

    public function isManual(): bool
    {
        return self::Manual === $this;
    }

    public function isStale(): bool
    {
        return self::Stale === $this;
    }

    public function isDynamic(): bool
    {
        return self::Dynamic === $this;
    }
}
