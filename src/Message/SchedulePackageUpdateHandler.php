<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsMessageHandler]
readonly class SchedulePackageUpdateHandler
{
    use PackageHandlerTrait;

    public function __construct(
        private PackageRepository $packageRepository,
        private MessageBusInterface $messenger,
    ) {
    }

    public function __invoke(SchedulePackageUpdate $message): void
    {
        $package = $this->getPackage($this->packageRepository, $message->packageId);

        $stamps = [new TransportNamesStamp('async')];

        if ($message->randomTime) {
            // Delay message up to 12 minutes
            $stamps[] = new DelayStamp(random_int(1, 720) * 1000);
        }

        $this->messenger->dispatch(new UpdatePackage($message->packageId, $message->source), $stamps);

        // todo prevent flush for every scheduled update but make sure scheduled updates are only performed
        $package->setUpdateScheduledAt(new \DateTimeImmutable());
        $this->packageRepository->save($package, true);
    }
}
