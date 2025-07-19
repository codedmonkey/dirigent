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

        $package->setRepositoryUrl('https://example.com/super/trouper');
        self::assertSame('https://example.com/super/trouper', $package->getBrowsableRepositoryUrl());

        $package->setRepositoryUrl('git://example.com/super/trouper');
        self::assertNull($package->getBrowsableRepositoryUrl());

        $package->setRepositoryUrl('git@example.com/super/trouper.git');
        self::assertNull($package->getBrowsableRepositoryUrl());
    }

    public function testPrettyBrowsableRepositoryUrl(): void
    {
        $package = new Package();

        $package->setRepositoryUrl('https://example.com/super/trouper');
        self::assertSame('example.com/super/trouper', $package->getPrettyBrowsableRepositoryUrl());

        $package->setRepositoryUrl('git://example.com/super/trouper');
        self::assertNull($package->getPrettyBrowsableRepositoryUrl());

        $package->setRepositoryUrl('git@example.com/super/trouper.git');
        self::assertNull($package->getPrettyBrowsableRepositoryUrl());
    }
}
