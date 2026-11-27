<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Distribution\ResolveDistributionMessage;
use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * @deprecated retained for messages queued before the distribution namespace migration
 */
#[AsMessage]
readonly class ResolveDistribution extends ResolveDistributionMessage
{
}
