<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

#[AsMessageHandler]
readonly class RemoveDistributionMessageHandler
{
    public function __construct(
        private DistributionRepository $distributionRepository,
        private DistributionFilesystem $distributionFilesystem,
    ) {
    }

    public function __invoke(RemoveDistributionMessage $message): void
    {
        if (null !== $this->distributionRepository->find($message->distributionId)) {
            throw new RecoverableMessageHandlingException("Distribution (id: {$message->distributionId}) still exists.", forceRetry: false);
        }

        $this->distributionFilesystem->removeFile($message->relativePath);
    }
}
