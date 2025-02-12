<?php

namespace CodedMonkey\Dirigent\Command;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Message\SchedulePackageUpdate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'packages:update',
    description: 'Schedules packages for update',
)]
class PackagesUpdateCommand extends Command
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
        private readonly MessageBusInterface $messenger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::OPTIONAL, 'Package to update')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces a re-crawl of all packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getOption('force');
        $packageName = $input->getArgument('package');

        $randomTimes = true;
        $reschedule = false;

        if ($packageName) {
            if (null === $package = $this->packageRepository->findOneByName($packageName)) {
                $io->error("Package $packageName not found");

                return Command::FAILURE;
            }

            $packages = [['id' => $package->getId()]];

            $randomTimes = false;
            $reschedule = true;
        } elseif ($force) {
            $packages = $this->packageRepository->getAllPackageIds();

            $reschedule = true;
        } else {
            $packages = $this->packageRepository->getStalePackages();
        }

        foreach ($packages as $package) {
            $this->messenger->dispatch(new SchedulePackageUpdate($package['id'], randomTime: $randomTimes, reschedule: $reschedule, forceRefresh: $force));
        }

        return Command::SUCCESS;
    }
}
