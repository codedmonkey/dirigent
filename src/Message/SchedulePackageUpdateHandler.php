<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsMessageHandler]
readonly class SchedulePackageUpdateHandler
{
    public function __construct(
        private PackageRepository $packageRepository,
        private MessageBusInterface $messenger,
    ) {
    }

    public function __invoke(SchedulePackageUpdate $message): void
    {
        $package = $this->packageRepository->find($message->packageId);

        if (!$message->reschedule && $package->isUpdateScheduled()) {
            return;
        }

        $updateMessage = Envelope::wrap(new UpdatePackage($message->packageId, scheduled: true, forceRefresh: $message->forceRefresh))
            ->with(new TransportNamesStamp('async'));

        if ($message->randomTime) {
            // Delay message up to 12 minutes
            $updateMessage = $updateMessage->with(new DelayStamp(random_int(1, 720) * 1000));
        }

        $package->setUpdateScheduledAt(new \DateTime());

        $this->packageRepository->save($package, true);

        $this->messenger->dispatch($updateMessage);
    }
}
