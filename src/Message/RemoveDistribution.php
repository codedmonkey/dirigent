<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class RemoveDistribution
{
    public function __construct(
        public int $distributionId,
        public string $relativePath,
    ) {
    }
}
