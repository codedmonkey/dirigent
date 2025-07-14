<?php

namespace CodedMonkey\Dirigent\Command;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Message\SchedulePackageUpdate;
use CodedMonkey\Dirigent\Message\UpdatePackage;
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
            ->addArgument('package', InputArgument::IS_ARRAY, 'Package to update')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Update all packages')
            ->addOption('sync', null, InputOption::VALUE_NONE, 'Update packages synchronously')
            ->setHelp(<<<'TXT'
                The <info>%command.name%</info> command schedules packages in the registry for update:

                  <info>%command.full_name%</info>

                By default, only packages that have passed the periodic update interval will be scheduled for update.

                Use the <comment>--all</comment> option to schedule all packages for update instead:

                  <info>%command.full_name% --all</info>

                It's possible to update specific packages by passing their name as arguments:

                  <info>%command.full_name% psr/cache psr/log</info>

                Use the <comment>--sync</comment> option to update packages synchronously:

                  <info>%command.full_name% psr/cache psr/log --sync</info>
            TXT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $all = $input->getOption('all');
        $packageNames = $input->getArgument('package');
        $sync = $input->getOption('sync');

        if ($sync && !count($packageNames)) {
            $io->error('Specify a package to update when using the --sync option.');

            return Command::FAILURE;
        }

        // Force refresh updates even if alread
        $forceRefresh = false;
        // Randomize time of updates
        $randomTimes = true;
        // Schedule update even if already scheduled
        $reschedule = false;

        if (count($packageNames)) {
            $packageIds = [];
            foreach ($packageNames as $packageName) {
                if (null === $package = $this->packageRepository->findOneByName($packageName)) {
                    $io->error("Package $packageName not found");

                    return Command::FAILURE;
                }

                $io->writeln("Scheduling package $packageName for update...");
                $packageIds[] = $package->getId();
            }

            $forceRefresh = true;
            $randomTimes = false;
            $reschedule = true;
        } elseif ($all) {
            $io->writeln('Scheduling all packages for update...');
            $packageIds = $this->packageRepository->getAllPackageIds();

            $forceRefresh = true;
            $reschedule = true;
        } else {
            $io->writeln('Scheduling stale packages for update...');
            $packageIds = $this->packageRepository->getStalePackageIds();
        }

        if ($sync) {
            foreach ($packageIds as $packageId) {
                $this->messenger->dispatch(new UpdatePackage($packageId, forceRefresh: $forceRefresh));
            }

            $packageCount = count($packageIds);
            $io->success("Updated $packageCount package(s).");
        }

        foreach ($packageIds as $packageId) {
            $this->messenger->dispatch(new SchedulePackageUpdate($packageId, randomTime: $randomTimes, reschedule: $reschedule, forceRefresh: $forceRefresh));
        }

        $packageCount = count($packageIds);
        $io->success("Scheduled $packageCount package(s) for update.");

        return Command::SUCCESS;
    }
}
