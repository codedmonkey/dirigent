<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

#[AsMessageHandler]
readonly class RemoveDistributionHandler
{
    public function __construct(
        private DistributionRepository $distributionRepository,
        private PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function __invoke(RemoveDistribution $message): void
    {
        if (null !== $this->distributionRepository->find($message->distributionId)) {
            throw new RecoverableMessageHandlingException("Distribution (id: {$message->distributionId}) still exists.", forceRetry: false);
        }

        $this->distributionResolver->removeFile($message->relativePath);
    }
}
