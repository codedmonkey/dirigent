<?php

namespace CodedMonkey\Conductor\Message;

readonly class TrackDownloads
{
    public function __construct(
        public array $downloads,
        public \DateTimeInterface $downloadedAt,
    ) {
    }
}
