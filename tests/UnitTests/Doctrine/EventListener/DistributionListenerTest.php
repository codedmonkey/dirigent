<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\EventListener\DistributionListener;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DistributionListener::class)]
class DistributionListenerTest extends TestCase
{
    public function testPreRemoveDeletesDistributionFile(): void
    {
        $distribution = $this->createStub(Distribution::class);
        $resolver = $this->createMock(PackageDistributionResolver::class);
        $resolver->expects(self::once())->method('remove')->with($distribution);

        new DistributionListener($resolver)->preRemove($distribution);
    }
}
