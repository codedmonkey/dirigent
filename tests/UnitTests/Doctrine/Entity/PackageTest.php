<?php

namespace CodedMonkey\Dirigent\Tests\UnitTests\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Package::class)]
class PackageTest extends TestCase
{
    public function testBrowsableRepositoryUrl(): void
    {
        $package = new Package();

        $package->setRepositoryUrl('https://github.com/super/trouper');
        self::assertSame('https://github.com/super/trouper', $package->getBrowsableRepositoryUrl());
        self::assertSame('github.com/super/trouper', $package->getPrettyBrowsableRepositoryUrl());

        $package->setRepositoryUrl('https://example.com/super/trouper');
        self::assertNull($package->getBrowsableRepositoryUrl());
        self::assertNull($package->getPrettyBrowsableRepositoryUrl());

        $package->setRepositoryUrl('git://example.com/super/trouper');
        self::assertNull($package->getBrowsableRepositoryUrl());
        self::assertNull($package->getPrettyBrowsableRepositoryUrl());

        $package->setRepositoryUrl('git@example.com/super/trouper.git');
        self::assertNull($package->getBrowsableRepositoryUrl());
        self::assertNull($package->getPrettyBrowsableRepositoryUrl());
    }
}
