<?php

namespace CodedMonkey\Dirigent\Message;

readonly class TrackInstallations
{
    public function __construct(
        public array $installations,
        public \DateTimeInterface $installedAt,
    ) {
    }
}
