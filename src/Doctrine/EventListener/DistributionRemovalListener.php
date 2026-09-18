<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Package\DistributionRemovalScheduler;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;

#[AsDoctrineListener(Events::postFlush)]
#[AsDoctrineListener(Events::onClear)]
readonly class DistributionRemovalListener
{
    public function __construct(
        private DistributionRemovalScheduler $removalScheduler,
    ) {
    }

    public function postFlush(): void
    {
        $this->removalScheduler->dispatch();
    }

    public function onClear(): void
    {
        $this->removalScheduler->clear();
    }
}
