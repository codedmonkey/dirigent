<?php

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class UpdatePackage
{
    public function __construct(
        public int $packageId,
        public bool $scheduled = false,
        public bool $forceRefresh = false,
    ) {
    }

    public function isDynamicRequest(): bool
    {
        return !$this->scheduled && !$this->forceRefresh;
    }
}
