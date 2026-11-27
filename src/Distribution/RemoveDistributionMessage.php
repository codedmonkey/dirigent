<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class RemoveDistributionMessage
{
    public function __construct(
        public int $distributionId,
        public string $relativePath,
    ) {
    }
}
