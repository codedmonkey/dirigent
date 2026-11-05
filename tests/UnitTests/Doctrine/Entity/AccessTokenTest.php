<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\AccessToken;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

#[CoversClass(AccessToken::class)]
class AccessTokenTest extends TestCase
{
    public function testGeneratedTokenHasFullLengthSecret(): void
    {
        self::assertMatchesRegularExpression('/^dirigent-[0-9a-f]{64}$/D', $this->createAccessToken()->getPlainToken());
    }

    public function testGeneratedTokenCanBeHashedAndVerified(): void
    {
        $accessToken = $this->createAccessToken();
        $plainToken = $accessToken->getPlainToken();
        self::assertNotNull($plainToken);

        $hasher = new NativePasswordHasher(cost: 4);
        $accessToken->hashCredentials($hasher->hash($plainToken));

        self::assertNull($accessToken->getPlainToken());
        self::assertTrue($hasher->verify($accessToken->getToken(), $plainToken));
    }

    public function testTokenWithoutExpirationIsValid(): void
    {
        self::assertTrue($this->createAccessToken()->isValid());
    }

    public function testTokenWithFutureExpirationIsValid(): void
    {
        $accessToken = $this->createAccessToken();
        $accessToken->setExpiresAt(new \DateTimeImmutable('+1 day'));

        self::assertTrue($accessToken->isValid());
    }

    public function testTokenWithPastExpirationIsInvalid(): void
    {
        $accessToken = $this->createAccessToken();
        $accessToken->setExpiresAt(new \DateTimeImmutable('-1 day'));

        self::assertFalse($accessToken->isValid());
    }

    private function createAccessToken(): AccessToken
    {
        return new AccessToken(new User());
    }
}
