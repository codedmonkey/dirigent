<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

enum PackageFetchStrategy: string
{
    case Mirror = 'mirror';
    case Vcs = 'vcs';

    public function isMirror(): bool
    {
        return self::Mirror === $this;
    }

    public function isVcs(): bool
    {
        return self::Vcs === $this;
    }
}
