<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

enum PackageFetchStrategy: string
{
    case Mirror = 'mirror';
    case Source = 'source';
    case Vcs = 'vcs';

    public function isMirror(): bool
    {
        return self::Mirror === $this;
    }

    public function isSource(): bool
    {
        return self::Source === $this;
    }

    public function isVcs(): bool
    {
        return self::Vcs === $this;
    }
}
