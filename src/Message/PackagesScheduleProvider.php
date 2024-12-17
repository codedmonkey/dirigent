<?php

namespace CodedMonkey\Dirigent\Message;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('packages')]
readonly class PackagesScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        #[Autowire(param: 'dirigent.packages.periodic_updates')]
        private bool $periodicUpdatesEnabled,
    ) {
    }

    private Schedule $schedule;

    public function getSchedule(): Schedule
    {
        if (!isset($this->schedule)) {
            $schedule = new Schedule();

            if ($this->periodicUpdatesEnabled) {
                $schedule = $schedule->with(
                    RecurringMessage::every('15 minutes', new RunCommandMessage('packages:update')),
                );
            }

            $this->schedule = $schedule;
        }

        return $this->schedule;
    }
}
