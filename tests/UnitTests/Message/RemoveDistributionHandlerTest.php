<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Message;

use CodedMonkey\Dirigent\Distribution\DistributionFilesystem;
use CodedMonkey\Dirigent\Distribution\RemoveDistributionMessage;
use CodedMonkey\Dirigent\Distribution\RemoveDistributionMessageHandler;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

#[CoversClass(RemoveDistributionMessageHandler::class)]
class RemoveDistributionHandlerTest extends TestCase
{
    public function testInvokeRemovesFileAfterDistributionWasDeleted(): void
    {
        $distributionRepository = $this->createMock(DistributionRepository::class);
        $distributionRepository->expects(self::once())
            ->method('find')
            ->with(1972)
            ->willReturn(null);

        $distributionFilesystem = $this->createMock(DistributionFilesystem::class);
        $distributionFilesystem->expects(self::once())
            ->method('removeFile')
            ->with('vendor/package/version-reference.zip');

        $handler = new RemoveDistributionMessageHandler($distributionRepository, $distributionFilesystem);

        $handler(new RemoveDistributionMessage(1972, 'vendor/package/version-reference.zip'));
    }

    public function testInvokeRetriesWhileDistributionStillExists(): void
    {
        $distributionRepository = $this->createMock(DistributionRepository::class);
        $distributionRepository->expects(self::once())
            ->method('find')
            ->with(1972)
            ->willReturn($this->createStub(Distribution::class));

        $distributionFilesystem = $this->createMock(DistributionFilesystem::class);
        $distributionFilesystem->expects(self::never())->method('removeFile');

        $handler = new RemoveDistributionMessageHandler($distributionRepository, $distributionFilesystem);

        $this->expectException(RecoverableMessageHandlingException::class);
        $handler(new RemoveDistributionMessage(1972, 'vendor/package/version-reference.zip'));
    }
}
