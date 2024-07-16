<?php

namespace CodedMonkey\Conductor\Message;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('packages')]
class PackagesScheduleProvider implements ScheduleProviderInterface
{
    private Schedule $schedule;

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())->with(
            RecurringMessage::every('15 minutes', new RunCommandMessage('packages:update')),
        );
    }
}
