<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

#[CoversClass(AccessToken::class)]
class AccessTokenTest extends TestCase
{
    public function testGeneratedTokenHasFullLengthSecret(): void
    {
        self::assertMatchesRegularExpression('/^dirigent-[0-9a-f]{64}$/D', new AccessToken()->getPlainToken());
    }

    public function testGeneratedTokenCanBeHashedAndVerified(): void
    {
        $accessToken = new AccessToken();
        $plainToken = $accessToken->getPlainToken();
        self::assertNotNull($plainToken);

        $hasher = new NativePasswordHasher(cost: 4);
        $accessToken->hashCredentials($hasher->hash($plainToken));

        self::assertNull($accessToken->getPlainToken());
        self::assertTrue($hasher->verify($accessToken->getToken(), $plainToken));
    }

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
