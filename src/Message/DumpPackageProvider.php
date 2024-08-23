<?php

namespace CodedMonkey\Conductor\Message;

readonly class DumpPackageProvider
{
    public function __construct(
        public int $packageId,
    ) {
    }
}
