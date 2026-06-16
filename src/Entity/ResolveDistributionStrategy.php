<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ResolveDistributionStrategy: string implements TranslatableInterface
{
    case Disabled = 'none';
    case Dynamic = 'dynamic';
    case Automatic = 'auto';

    public function isDisabled(): bool
    {
        return self::Disabled === $this;
    }

    public function isDynamic(): bool
    {
        return self::Dynamic === $this;
    }

    public function isAutomatic(): bool
    {
        return self::Automatic === $this;
    }

    /**
     * Whether the strategy allows dynamic updates.
     *
     * Only the disabled strategy doesn't allow this.
     */
    public function allowDynamic(): bool
    {
        return !$this->isDisabled();
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans("package.resolve-distribution-strategy.$this->value");
    }
}
