<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use CodedMonkey\Dirigent\Message\ResolveDistributionHandler;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResolveDistributionHandler::class)]
class ResolveDistributionHandlerTest extends TestCase
{
    public function testInvokeIgnoresMissingMetadata(): void
    {
        $metadataRepository = $this->createMock(MetadataRepository::class);
        $metadataRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn(null);

        $distributionResolver = $this->createMock(PackageDistributionResolver::class);
        $distributionResolver->expects(self::never())->method('resolve');

        $handler = new ResolveDistributionHandler($metadataRepository, $distributionResolver);

        $handler(new ResolveDistribution(42, 'reference', 'zip'));
    }
}
