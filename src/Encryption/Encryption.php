<?php

declare(strict_types=1);

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

    public static function create(
        #[\SensitiveParameter]
        ?string $privateKey,
        #[\SensitiveParameter]
        ?string $privateKeyPath,
        #[\SensitiveParameter]
        ?string $publicKey,
        #[\SensitiveParameter]
        ?string $publicKeyPath,
        #[\SensitiveParameter]
        array $rotatedKeys,
        #[\SensitiveParameter]
        array $rotatedKeyPaths,
    ): self {
        $filesystem = new Filesystem();

        // Keys as parameter (env vars) takes precedence over file paths
        if (!$privateKey && $privateKeyPath) {
            if (!$filesystem->exists($privateKeyPath)) {
                throw new \RuntimeException("Private decryption key file \"$privateKeyPath\" does not exist.");
            }

            $privateKey = $filesystem->readFile($privateKeyPath);
        }

        if (!$privateKey) {
            throw new \RuntimeException('Unable to load encryption keys, missing the private key.');
        }

        if (!$publicKey && $publicKeyPath) {
            if (!$filesystem->exists($publicKeyPath)) {
                throw new \RuntimeException("Public encryption key file \"$publicKeyPath\" does not exist.");
            }

            $publicKey = $filesystem->readFile($publicKeyPath);
        }

        if (!$publicKey) {
            throw new \RuntimeException('Unable to load encryption keys, missing the public key.');
        }

        foreach ($rotatedKeyPaths as $rotatedKeyPath) {
            if (!$filesystem->exists($rotatedKeyPath)) {
                throw new \RuntimeException("Rotated key file \"$rotatedKeyPath\" does not exist.");
            }

            $rotatedKeys[] = $filesystem->readFile($rotatedKeyPath);
        }

        $binaryPrivateKey = sodium_hex2bin((string) $privateKey);
        $binaryPublicKey = sodium_hex2bin((string) $publicKey);
        $binaryRotatedKeys = array_map(
            sodium_hex2bin(...),
            $rotatedKeys,
        );

        return new self($binaryPrivateKey, $binaryPublicKey, $binaryRotatedKeys);
    }

    public function seal(#[\SensitiveParameter] string $data): string
    {
        $binary = sodium_crypto_box_seal($data, $this->publicKey);

        return sodium_bin2hex($binary);
    }

    public function reveal(#[\SensitiveParameter] string $data): string
    {
        $binary = sodium_hex2bin($data);
        $value = sodium_crypto_box_seal_open($binary, $this->privateKey);

        if (false !== $value) {
            return $value;
        }

        foreach ($this->rotatedKeys as $rotatedKey) {
            $value = sodium_crypto_box_seal_open($binary, $rotatedKey);

            if (false !== $value) {
                return $value;
            }
        }

        throw new EncryptionException('Unable to decrypt data.');
    }

    public function validate(): void
    {
        $value = 'thank you for the music';
        $sealedValue = $this->seal($value);

        $binary = sodium_hex2bin($sealedValue);
        $revealedValue = sodium_crypto_box_seal_open($binary, $this->privateKey);

        if (false === $revealedValue) {
            throw new EncryptionException('The encryption key is not valid.');
        }
    }
}
