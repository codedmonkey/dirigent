<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Message;

use CodedMonkey\Dirigent\Distribution\DistributionResolver;
use CodedMonkey\Dirigent\Distribution\ResolveDistributionMessage;
use CodedMonkey\Dirigent\Distribution\ResolveDistributionMessageHandler;
use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResolveDistributionMessageHandler::class)]
class ResolveDistributionHandlerTest extends TestCase
{
    public function testInvokeIgnoresMissingMetadata(): void
    {
        $metadataRepository = $this->createMock(MetadataRepository::class);
        $metadataRepository->expects(self::once())
            ->method('find')
            ->with(1972)
            ->willReturn(null);

        $distributionResolver = $this->createMock(DistributionResolver::class);
        $distributionResolver->expects(self::never())->method('resolve');

        $handler = new ResolveDistributionMessageHandler($metadataRepository, $distributionResolver);

        $handler(new ResolveDistributionMessage(1972, 'zip'));
    }
}
