<?php

namespace CodedMonkey\Dirigent\Command;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Entity\PackageUpdateSource;
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
    help: <<<'TXT'
        The <info>%command.name%</info> command schedules packages in the registry for update:

          <info>%command.full_name%</info>

        <fg=black;bg=yellow>                                                          </>
        <fg=black;bg=yellow> Make sure a worker is running, or use the --sync option. </>
        <fg=black;bg=yellow>                                                          </>

        By default, only packages that have passed the periodic update interval will be scheduled for update.

        Use the <comment>--all</comment> option to schedule all packages for update instead:

          <info>%command.full_name% --all</info>

        Package updates are scheduled somewhere in the next 12 minutes, except when specifying package names, then they are
        scheduled immediately. Check the worker's message queue if updates are not being executed.

        It's possible to update specific packages by passing their name as arguments:

          <info>%command.full_name% psr/cache psr/log</info>

        Use the <comment>--sync</comment> option to skip the worker and update packages synchronously:

          <info>%command.full_name% psr/cache psr/log --sync</info>
        TXT,
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
            ->addOption('all', null, InputOption::VALUE_NONE, 'Update all packages')
            ->addOption('sync', null, InputOption::VALUE_NONE, 'Updates packages synchronously');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $all = $input->getOption('all');
        $packageNames = (array) ($input->getArgument('package') ?? []);
        $sync = $input->getOption('sync');

        if ($sync && !count($packageNames)) {
            $io->error('Specify a package to update when using the --sync option.');

            return Command::FAILURE;
        }

        $randomTimes = true; // Randomize time of updates
        $source = PackageUpdateSource::Stale;

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

            $randomTimes = false;
            $source = PackageUpdateSource::Manual;
        } elseif ($all) {
            $io->writeln('Scheduling all packages for update...');
            $packageIds = $this->packageRepository->getAllPackageIds();

            $source = PackageUpdateSource::Manual;
        } else {
            $io->writeln('Scheduling stale packages for update...');
            $packageIds = $this->packageRepository->getStalePackageIds();
        }

        $packageCount = count($packageIds);

        if ($sync) {
            foreach ($packageIds as $packageId) {
                $this->messenger->dispatch(new UpdatePackage($packageId, $source));
            }

            $io->success("Updated $packageCount package(s).");
        } else {
            foreach ($packageIds as $packageId) {
                $this->messenger->dispatch(new SchedulePackageUpdate($packageId, $source, $randomTimes));
            }

            $io->success("Scheduled $packageCount package(s) for update.");
        }

        return Command::SUCCESS;
    }
}
