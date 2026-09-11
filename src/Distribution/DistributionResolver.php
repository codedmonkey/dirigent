<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class DistributionResolver
{
    public function __construct(
        private readonly DistributionBuilder $distributionBuilder,
        private readonly DistributionFilesystem $distributionFilesystem,
        private readonly DistributionMirrorer $distributionMirrorer,
        private readonly MessageBusInterface $messenger,
        private readonly DistributionRepository $distributionRepository,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'dirigent.distributions.build')]
        private readonly bool $buildDistributions,
        #[Autowire(param: 'dirigent.distributions.mirror')]
        private readonly bool $mirrorDistributions,
        #[Autowire(param: 'dirigent.distributions.dev_versions')]
        private readonly bool $includeDevVersions,
    ) {
    }

    public function resolve(Metadata $metadata, string $type, bool $async): bool
    {
        if ($this->distributionFilesystem->exists($metadata, $type)) {
            return true;
        }

        if (null === $strategy = $this->resolveFetchStrategy($metadata)) {
            return false;
        }

        $currentType = $strategy->isMirror() ? $metadata->getDistributionType() : 'zip';

        if ($type !== $currentType) {
            return false;
        }

        if ($async) {
            // Resolve the distribution asynchronously so it's available in the future now that we know it was requested
            $this->messenger->dispatch(new ResolveDistribution($metadata->getId(), $type), [
                new TransportNamesStamp('async'),
            ]);

            // Still return false so the service resolving the distribution doesn't try to fetch it anyway
            return false;
        }

        $path = $this->distributionFilesystem->path($metadata, $type);
        $lock = $this->distributionFilesystem->createDistributionLock($path);

        try {
            if ($this->distributionFilesystem->exists($metadata, $type)) {
                return true;
            }

            if (null === $distribution = $this->distributionRepository->findOneByMetadataAndType($metadata, $type)) {
                $distribution = new Distribution($metadata, $type);
            }

            $result = false;

            // Build the distribution from VCS source
            if ($strategy->isVcs()) {
                $buildException = null;

                try {
                    $result = $this->distributionBuilder->build($distribution);
                } catch (\RuntimeException $exception) {
                    $buildException = $exception;
                }

                if (!$result) {
                    $this->logger->warning('Failed to build distribution for {package} at {reference}.', [
                        'package' => $metadata->getPackage()->getName(),
                        'reference' => $distribution->getReference(),
                        'exception' => $buildException,
                    ]);

                    $this->distributionFilesystem->removePath($path);

                    if (
                        $this->mirrorDistributions
                        && $metadata->hasDistribution()
                        && $type === $metadata->getDistributionType()
                    ) {
                        // Mirror the distribution if possible
                        $strategy = PackageFetchStrategy::Mirror;
                    }
                }
            }

            if ($strategy->isMirror()) {
                $result = $this->distributionMirrorer->mirror($distribution, $path);
            }

            if ($result) {
                $distribution->setResolvedAt();

                try {
                    $this->distributionRepository->save($distribution, true);
                } catch (\Throwable $exception) {
                    // Remove file immediately if saving the distribution to the database failed
                    $this->distributionFilesystem->removePath($path);

                    throw $exception;
                }
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * Resolve the optimal fetch strategy for a package'x distribution.
     */
    private function resolveFetchStrategy(Metadata $metadata): ?PackageFetchStrategy
    {
        $fetchStrategy = $metadata->getPackage()->getFetchStrategy();

        if (!$this->includeDevVersions && $metadata->getVersion()->isDevelopment()) {
            // This configuration doesn't support development versions
            return null;
        } elseif ($this->buildDistributions && $fetchStrategy->isVcs() && 'git' === $metadata->getSourceType()) {
            // Only build distributions if the fetch strategy is VCS (not source because it might not contain VCS data) with Git
            return PackageFetchStrategy::Vcs;
        } elseif (
            $this->mirrorDistributions
            && ($fetchStrategy->isMirror() || $metadata->hasDistribution())
        ) {
            // Allow mirroring directly or as fallback when building the distribution isn't allowed
            return PackageFetchStrategy::Mirror;
        }

        return null;
    }
}
