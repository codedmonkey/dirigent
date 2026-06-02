<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class TrackInstallations
{
    public function __construct(
        public array $installations,
        public \DateTimeImmutable $installedAt,
    ) {
    }
}
