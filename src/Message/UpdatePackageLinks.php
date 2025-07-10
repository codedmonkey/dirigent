<?php

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class UpdatePackageLinks
{
    public function __construct(
        public int $packageId,
        public string $versionName,
    ) {
    }
}
