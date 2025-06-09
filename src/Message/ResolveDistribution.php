<?php

namespace CodedMonkey\Dirigent\Message;

readonly class ResolveDistribution
{
    public function __construct(
        public int $versionId,
        public ?string $reference = null,
        public ?string $type = null,
    ) {
    }
}
