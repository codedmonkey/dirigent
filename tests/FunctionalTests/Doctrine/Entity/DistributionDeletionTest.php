<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Message\RemoveDistribution;
use CodedMonkey\Dirigent\Message\RemoveDistributionHandler;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\KernelTestCaseTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;

class DistributionDeletionTest extends KernelTestCase
{
    use EntityManagerTestTrait;
    use KernelTestCaseTrait;
    use MockEntityFactoryTrait;

    public function testDeletingDistributionDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);
        $this->persistEntities($package, $version, $metadata, $distribution);

        $this->removeEntities($distribution);

        $this->runQueuedDistributionRemovals();

        self::assertFileDoesNotExist($path);
    }

    public function testDeletingMetadataDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);

        // Metadata can't be deleted if it's the current metadata of a version, so we replace it
        $currentMetadata = $this->createMockMetadata($version);
        $version->setCurrentMetadata($currentMetadata);

        $this->persistEntities($package, $version, $metadata, $currentMetadata, $distribution);

        $this->removeEntities($metadata);

        $this->runQueuedDistributionRemovals();

        self::assertFileDoesNotExist($path);
    }

    public function testDeletingPackageDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);
        $this->persistEntities($package, $version, $metadata, $distribution);

        $this->removeEntities($package);

        $this->runQueuedDistributionRemovals();

        self::assertFileDoesNotExist($path);
    }

    public function testDeletingVersionDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);
        $this->persistEntities($package, $version, $metadata, $distribution);

        $this->removeEntities($version);

        $this->runQueuedDistributionRemovals();

        self::assertFileDoesNotExist($path);
    }

    public function testRollingBackDistributionDeletionKeepsDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);
        $this->persistEntities($package, $version, $metadata, $distribution);

        $entityManager = $this->getService(EntityManagerInterface::class);

        $entityManager->getConnection()->beginTransaction();
        $entityManager->remove($distribution);
        $entityManager->flush();
        $entityManager->getConnection()->rollBack();
        $entityManager->clear();

        self::assertFileExists($path);
        self::assertSame([], $this->receiveDistributionRemovals());

        new Filesystem()->remove($path);
    }

    /**
     * @return array{Distribution, string}
     */
    private function createDistributionFile(Metadata $metadata): array
    {
        $metadata->setDistributionReference('reference');

        $distribution = new Distribution($metadata, 'zip');
        $distribution->setResolvedAt();

        $path = $this->getService(PackageDistributionResolver::class)->path($metadata, $distribution->getType());
        new Filesystem()->dumpFile($path, 'distribution');

        return [$distribution, $path];
    }

    private function runQueuedDistributionRemovals(): void
    {
        self::assertNotEmpty($messages = $this->receiveDistributionRemovals());

        $this->getService(EntityManagerInterface::class)->clear();
        $handler = $this->getService(RemoveDistributionHandler::class);

        foreach ($messages as $message) {
            $handler($message);
        }
    }

    /**
     * @return RemoveDistribution[]
     */
    private function receiveDistributionRemovals(): array
    {
        /** @var ReceiverInterface $transport */
        $transport = $this->getService(ReceiverInterface::class, 'messenger.transport.async');
        $messages = [];

        while ($envelopes = $transport->get()) {
            foreach ($envelopes as $envelope) {
                if ($envelope->getMessage() instanceof RemoveDistribution) {
                    $messages[] = $envelope->getMessage();
                }

                $transport->ack($envelope);
            }
        }

        return $messages;
    }
}
