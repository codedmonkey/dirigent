<?php

namespace CodedMonkey\Dirigent;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ConsoleApplication extends Application
{
    private bool $commandsRegistered = false;

    public function __construct(
        private KernelInterface $kernel,
    ) {
        parent::__construct('Dirigent', Kernel::VERSION);
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->registerCommands();

        $this->setDispatcher($this->kernel->getContainer()->get('event_dispatcher'));

        return parent::doRun($input, $output);
    }

    private function registerCommands(): void
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $this->kernel->boot();

        $container = $this->kernel->getContainer();
        $commands = $container->get('dirigent_command_locator');

        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
