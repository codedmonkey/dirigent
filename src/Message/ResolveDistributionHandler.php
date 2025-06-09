<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ResolveDistributionHandler
{
    public function __construct(
        private VersionRepository $versionRepository,
        private PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function __invoke(ResolveDistribution $message): void
    {
        $version = $this->versionRepository->find($message->versionId);

        $this->distributionResolver->resolve($version, $message->reference, $message->type, async: false);
    }
}
