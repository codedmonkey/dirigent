<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class ResolveDistributionMessage
{
    public function __construct(
        public int $metadataId,
        public string $type,
    ) {
    }
}
