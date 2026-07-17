<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum PackageFetchStrategy: string implements TranslatableInterface
{
    case Mirror = 'mirror';
    case Source = 'source';
    case Vcs = 'vcs';

    public static function repositoryCases(): array
    {
        return [
            self::Source,
            self::Vcs,
        ];
    }

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

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans("package.fetch-strategy.$this->value");
    }
}
