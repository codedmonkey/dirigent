<?php

namespace CodedMonkey\Dirigent\Tests\UnitTests\Encryption;

use CodedMonkey\Dirigent\Encryption\Encryption;
use CodedMonkey\Dirigent\Encryption\EncryptionException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class EncryptionTest extends TestCase
{
    public const DATA = 'mamma mia';

    protected function tearDown(): void
    {
        $files = Finder::create()
            ->files()
            ->in(__DIR__ . '/keys')
            ->name('*.key');

        foreach ($files as $file) {
            (new Filesystem())->remove($file->getRealPath());
        }
    }

    public function testSeal(): void
    {
        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($privateKey);

        $encryption = new Encryption($privateKey, $publicKey, []);

        $sealedData = $encryption->seal(self::DATA);

        $this->assertSame(self::DATA, sodium_crypto_box_seal_open(sodium_hex2bin($sealedData), $privateKey));
    }

    public function testReveal(): void
    {
        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($privateKey);

        $encryption = new Encryption($privateKey, $publicKey, []);

        $sealedData = sodium_bin2hex(sodium_crypto_box_seal(self::DATA, $publicKey));

        $this->assertSame(self::DATA, $encryption->reveal($sealedData));
    }

    public function testRevealWithRotatedKey(): void
    {
        $rotatedPrivateKey = sodium_crypto_box_keypair();
        $rotatedPublicKey = sodium_crypto_box_publickey($rotatedPrivateKey);

        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($privateKey);

        $encryption = new Encryption($privateKey, $publicKey, [$rotatedPrivateKey]);

        $sealedData = sodium_bin2hex(sodium_crypto_box_seal(self::DATA, $rotatedPublicKey));

        $this->assertSame(self::DATA, $encryption->reveal($sealedData));
    }

    public function testRevealFailsWithInvalidPublicKey(): void
    {
        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey(sodium_crypto_box_keypair());

        $encryption = new Encryption($privateKey, $publicKey, []);

        $sealedData = $encryption->seal(self::DATA);

        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('Unable to decrypt data');

        $encryption->reveal($sealedData);
    }

    public function testCreateFromHex(): void
    {
        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($privateKey);

        $encryption = Encryption::create(
            sodium_bin2hex($privateKey),
            null,
            sodium_bin2hex($publicKey),
            null,
            [],
            [],
        );

        $this->validateEncryption($encryption);
    }

    public function testCreateFromFiles(): void
    {
        $filesystem = new Filesystem();

        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($privateKey);

        $privateKeyPath = __DIR__ . '/keys/private.key';
        $publicKeyPath = __DIR__ . '/keys/public.key';

        $filesystem->dumpFile($privateKeyPath, sodium_bin2hex($privateKey));
        $filesystem->dumpFile($publicKeyPath, sodium_bin2hex($publicKey));

        $encryption = Encryption::create(
            null,
            $privateKeyPath,
            null,
            $publicKeyPath,
            [],
            [],
        );

        $this->validateEncryption($encryption);
    }

    public function testValidate(): void
    {
        $this->expectNotToPerformAssertions();

        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($privateKey);

        $encryption = new Encryption($privateKey, $publicKey, []);

        $encryption->validate();
    }

    public function testValidateFailsWithInvalidPublicKey(): void
    {
        $privateKey = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey(sodium_crypto_box_keypair());

        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('The encryption key is not valid.');

        $encryption = new Encryption($privateKey, $publicKey, []);

        $encryption->validate();
    }

    private function validateEncryption(Encryption $encryption): void
    {
        $data = $encryption->reveal($encryption->seal(self::DATA));

        $this->assertSame(self::DATA, $data);
    }
}
