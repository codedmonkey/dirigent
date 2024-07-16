<?php

namespace CodedMonkey\Conductor\Message;

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
