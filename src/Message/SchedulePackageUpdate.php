<?php

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class SchedulePackageUpdate
{
    public function __construct(
        public int $packageId,
        public bool $randomTime = false,
        public bool $reschedule = false,
        public bool $forceRefresh = false,
    ) {
    }
}
