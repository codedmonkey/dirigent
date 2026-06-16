<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum RegistryPackageMirroring: string implements TranslatableInterface
{
    case Disabled = 'none';
    case Manual = 'manual';
    case Automatic = 'auto';

    public function isDisabled(): bool
    {
        return self::Disabled === $this;
    }

    public function isManual(): bool
    {
        return self::Manual === $this;
    }

    public function isAutomatic(): bool
    {
        return self::Automatic === $this;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans("registry.package-mirroring.$this->value");
    }
}
