<?php

namespace CodedMonkey\Dirigent\Message;

readonly class UpdatePackageLinks
{
    public function __construct(
        public int $packageId,
        public string $versionName,
    ) {
    }
}
