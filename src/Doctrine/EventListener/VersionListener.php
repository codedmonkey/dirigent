<?php

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\PackageDistributionStrategy;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsEntityListener(Events::postPersist, entity: Version::class)]
#[AsEntityListener(Events::postUpdate, entity: Version::class)]
readonly class VersionListener
{
    public function __construct(
        private MessageBusInterface $messenger,
        #[Autowire(param: 'dirigent.distributions.enabled')]
        private bool $distributionsEnabled,
        #[Autowire(param: 'dirigent.distributions.dev_versions')]
        private bool $resolveDevDistributions,
    ) {
    }

    public function postPersist(Version $version): void
    {
        $this->resolveDistribution($version);
    }

    public function postUpdate(Version $version): void
    {
        // todo only update if reference or type changed
        $this->resolveDistribution($version);
    }

    private function resolveDistribution(Version $version): void
    {
        if (
            !$this->distributionsEnabled
            || ($version->isDevelopment() && !$this->resolveDevDistributions)
        ) {
            return;
        }

        if ($version->getPackage()->getDistributionStrategy()->isAutomatic()) {
            $message = Envelope::wrap(new ResolveDistribution($version->getId()))
                ->with(new TransportNamesStamp('async'));
            $this->messenger->dispatch($message);
        }
    }
}
