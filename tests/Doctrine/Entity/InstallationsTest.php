<?php

namespace CodedMonkey\Dirigent\Tests\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\AbstractInstallations;
use PHPUnit\Framework\TestCase;

class InstallationsTest extends TestCase
{
    public function testIncrease(): void
    {
        $installs = new MockInstallations();

        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-07'));
        $installs->increase(new \DateTimeImmutable('2024-05-07'));
        $installs->increase(new \DateTimeImmutable('2024-05-08'));

        self::assertSame([
            '20240506' => 4,
            '20240507' => 2,
            '20240508' => 1,
        ], $installs->getData());
        self::assertSame(7, $installs->getTotal());
        self::assertInstanceOf(\DateTimeImmutable::class, $installs->getUpdatedAt());
        self::assertNull($installs->getMergedAt());
    }

    public function testMergeData(): void
    {
        $installs = new MockInstallations();

        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-06'));
        $installs->increase(new \DateTimeImmutable('2024-05-07'));
        $installs->increase(new \DateTimeImmutable('2024-05-07'));
        $installs->increase(new \DateTimeImmutable('2024-05-08'));

        $installs->mergeData();

        self::assertSame([
            '20240506' => 4,
            '20240507' => 2,
            '20240508' => 1,
        ], $installs->getData());
        self::assertSame(7, $installs->getTotal());
        self::assertInstanceOf(\DateTimeImmutable::class, $installs->getUpdatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $installs->getMergedAt());
    }
}

class MockInstallations extends AbstractInstallations
{
}
