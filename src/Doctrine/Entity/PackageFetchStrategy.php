<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Entity;

enum PackageFetchStrategy: string
{
    case Mirror = 'mirror';
    case Vcs = 'vcs';
}
