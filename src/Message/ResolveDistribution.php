<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class ResolveDistribution
{
    public function __construct(
        public int $metadataId,
        public string $reference,
        public string $type,
    ) {
    }
}
