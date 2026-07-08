<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ResolveDistributionHandler
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function __invoke(ResolveDistribution $message): void
    {
        $metadata = $this->metadataRepository->find($message->metadataId);

        $this->distributionResolver->resolve($metadata, $message->reference, $message->type, async: false);
    }
}
