<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum RegistryPackageMirroring: string implements TranslatableInterface
{
    case Disabled = 'none';
    case Manual = 'manual';
    case Automatic = 'auto';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return sprintf('registry.package-mirroring.%s', $this->value);
    }
}
