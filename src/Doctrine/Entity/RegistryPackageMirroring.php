<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Entity;

enum RegistryPackageMirroring: string
{
    case Disabled = 'none';
    case Manual = 'manual';
    case Automatic = 'auto';
}
