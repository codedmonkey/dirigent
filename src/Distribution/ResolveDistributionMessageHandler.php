<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ResolveDistributionMessageHandler
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private DistributionResolver $distributionResolver,
    ) {
    }

    public function __invoke(ResolveDistributionMessage $message): void
    {
        if (null === $metadata = $this->metadataRepository->find($message->metadataId)) {
            return;
        }

        $this->distributionResolver->resolve($metadata, $message->type, async: false);
    }
}
