<?php

namespace CodedMonkey\Dirigent\Message;

readonly class UpdatePackage
{
    public function __construct(
        public int $packageId,
        public bool $scheduled = false,
        public bool $forceRefresh = false,
    ) {
    }
}
