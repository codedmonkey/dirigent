<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Entity\PackageUpdateSource;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class SchedulePackageUpdate
{
    public function __construct(
        public int $packageId,
        public PackageUpdateSource $source,
        public bool $randomTime = false,
    ) {
    }
}
