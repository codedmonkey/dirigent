<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

enum PackageFetchStrategy: string
{
    case Mirror = 'mirror';
    case Vcs = 'vcs';
}
