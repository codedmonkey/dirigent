<?php

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
