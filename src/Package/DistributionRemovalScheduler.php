<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Message\RemoveDistribution;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class DistributionRemovalScheduler
{
    /**
     * @var array<int, RemoveDistribution>
     */
    private array $pendingRemovals = [];

    public function __construct(
        private readonly MessageBusInterface $messenger,
        private readonly DistributionRepository $distributionRepository,
        private readonly PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function schedule(Distribution $distribution): void
    {
        if (null === $distributionId = $distribution->getId()) {
            return;
        }

        $this->pendingRemovals[$distributionId] = new RemoveDistribution(
            $distributionId,
            $this->distributionResolver->relativePath($distribution),
        );
    }

    public function scheduleMetadata(Metadata $metadata): void
    {
        foreach ($this->distributionRepository->findByMetadata($metadata) as $distribution) {
            $this->schedule($distribution);
        }
    }

    public function schedulePackage(Package $package): void
    {
        foreach ($this->distributionRepository->findByPackage($package) as $distribution) {
            $this->schedule($distribution);
        }
    }

    public function scheduleVersion(Version $version): void
    {
        foreach ($this->distributionRepository->findByVersion($version) as $distribution) {
            $this->schedule($distribution);
        }
    }

    public function dispatch(): void
    {
        foreach ($this->pendingRemovals as $distributionId => $message) {
            $this->messenger->dispatch($message, [
                new TransportNamesStamp('async'),
            ]);

            unset($this->pendingRemovals[$distributionId]);
        }
    }

    public function clear(): void
    {
        $this->pendingRemovals = [];
    }
}
