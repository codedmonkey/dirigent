<?php

namespace CodedMonkey\Dirigent\Encryption;

use Symfony\Component\Filesystem\Filesystem;

readonly class Encryption
{
    public function __construct(
        #[\SensitiveParameter]
        private string $privateKey,
        #[\SensitiveParameter]
        private string $publicKey,
        #[\SensitiveParameter]
        private array $rotatedKeys,
    ) {
    }

    public static function createFromFiles(
        string $privateKeyPath,
        string $publicKeyPath,
        array $rotatedKeyPaths,
    ): self {
        $filesystem = new Filesystem();

        if (!$filesystem->exists($privateKeyPath)) {
            throw new \RuntimeException("Private decryption key file \"$privateKeyPath\" does not exist.");
        } elseif (!$filesystem->exists($publicKeyPath)) {
            throw new \RuntimeException("Public encryption key file \"$publicKeyPath\" does not exist.");
        }

        foreach ($rotatedKeyPaths as $rotatedKeyPath) {
            if (!$filesystem->exists($rotatedKeyPath)) {
                throw new \RuntimeException("Rotated key file \"$rotatedKeyPath\" does not exist.");
            }
        }

        $privateKey = $filesystem->readFile($privateKeyPath);
        $publicKey = $filesystem->readFile($publicKeyPath);
        $rotatedKeys = array_map(
            fn (string $rotatedKeyPath): string => $filesystem->readFile($rotatedKeyPath),
            $rotatedKeyPaths
        );

        return self::createFromHex($privateKey, $publicKey, $rotatedKeys);
    }

    public static function createFromHex(
        #[\SensitiveParameter]
        string $privateKey,
        #[\SensitiveParameter]
        string $publicKey,
        #[\SensitiveParameter]
        array $rotatedKeys,
    ): self {
        $binaryPrivateKey = sodium_hex2bin($privateKey);
        $binaryPublicKey = sodium_hex2bin($publicKey);
        $binaryRotatedKeys = array_map(
            fn (string $rotatedKey): string => sodium_hex2bin($rotatedKey),
            $rotatedKeys,
        );

        return new self($binaryPrivateKey, $binaryPublicKey, $binaryRotatedKeys);
    }

    public function seal(#[\SensitiveParameter] string $data): string
    {
        return sodium_crypto_box_seal($data, $this->publicKey);
    }

    public function reveal(#[\SensitiveParameter] string $data): string
    {
        $value = sodium_crypto_box_seal_open($data, $this->privateKey);

        if (false !== $value) {
            return $value;
        }

        foreach ($this->rotatedKeys as $rotatedKey) {
            $value = sodium_crypto_box_seal_open($data, $rotatedKey);

            if (false !== $value) {
                return $value;
            }
        }

        throw new EncryptionException('Unable to decrypt data');
    }
}
