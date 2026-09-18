<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Message;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Message\RemoveDistribution;
use CodedMonkey\Dirigent\Message\RemoveDistributionHandler;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

#[CoversClass(RemoveDistributionHandler::class)]
class RemoveDistributionHandlerTest extends TestCase
{
    public function testInvokeRemovesFileAfterDistributionWasDeleted(): void
    {
        $distributionRepository = $this->createMock(DistributionRepository::class);
        $distributionRepository->expects(self::once())
            ->method('find')
            ->with(1972)
            ->willReturn(null);

        $distributionResolver = $this->createMock(PackageDistributionResolver::class);
        $distributionResolver->expects(self::once())
            ->method('removeFile')
            ->with('vendor/package/version-reference.zip');

        $handler = new RemoveDistributionHandler($distributionRepository, $distributionResolver);

        $handler(new RemoveDistribution(1972, 'vendor/package/version-reference.zip'));
    }

    public function testInvokeRetriesWhileDistributionStillExists(): void
    {
        $distributionRepository = $this->createMock(DistributionRepository::class);
        $distributionRepository->expects(self::once())
            ->method('find')
            ->with(1972)
            ->willReturn($this->createStub(Distribution::class));

        $distributionResolver = $this->createMock(PackageDistributionResolver::class);
        $distributionResolver->expects(self::never())->method('removeFile');

        $handler = new RemoveDistributionHandler($distributionRepository, $distributionResolver);

        $this->expectException(RecoverableMessageHandlingException::class);
        $handler(new RemoveDistribution(1972, 'vendor/package/version-reference.zip'));
    }
}
