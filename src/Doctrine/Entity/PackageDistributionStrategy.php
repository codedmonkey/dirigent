<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum PackageDistributionStrategy: string implements TranslatableInterface
{
    case Disabled = 'none';
    case Dynamic = 'dynamic';
    case Automatic = 'auto';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans("package.distribution-strategy.{$this->value}");
    }

    public function allowDynamic(): bool
    {
        return self::Disabled !== $this;
    }
}
