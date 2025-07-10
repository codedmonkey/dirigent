<?php

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class DumpPackageProvider
{
    public function __construct(
        public int $packageId,
    ) {
    }
}
