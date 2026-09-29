<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccessToken::class)]
class AccessTokenTest extends TestCase
{
    public function testTokenWithoutExpirationIsValid(): void
    {
        self::assertTrue(new AccessToken()->isValid());
    }

    public function testTokenWithFutureExpirationIsValid(): void
    {
        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTimeImmutable('+1 day'));

        self::assertTrue($accessToken->isValid());
    }

    public function testTokenWithPastExpirationIsInvalid(): void
    {
        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTimeImmutable('-1 day'));

        self::assertFalse($accessToken->isValid());
    }
}
