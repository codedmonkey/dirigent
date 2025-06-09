<?php

namespace CodedMonkey\Dirigent\Message;

readonly class ResolveDistribution
{
    public function __construct(
        public int $versionId,
        public string $reference,
        public string $type,
    ) {
    }
}
