<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

enum RegistryPackageMirroring: string
{
    case Disabled = 'none';
    case Manual = 'manual';
    case Automatic = 'auto';
}
