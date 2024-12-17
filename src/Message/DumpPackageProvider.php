<?php

namespace CodedMonkey\Dirigent\Message;

readonly class DumpPackageProvider
{
    public function __construct(
        public int $packageId,
    ) {
    }
}
