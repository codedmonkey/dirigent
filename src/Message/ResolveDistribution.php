<?php

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class ResolveDistribution
{
    public function __construct(
        public int $versionId,
        public ?string $reference = null,
        public ?string $type = null,
    ) {
    }
}
