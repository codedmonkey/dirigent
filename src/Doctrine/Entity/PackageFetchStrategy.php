<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum PackageFetchStrategy: string implements TranslatableInterface
{
    case Mirror = 'mirror';
    case Vcs = 'vcs';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return sprintf('package.fetch-strategy.%s', $this->value);
    }
}
