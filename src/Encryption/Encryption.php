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
        $useFiles = !$privateKey && !$publicKey;

        if ($useFiles) {
            if ($privateKey || $publicKey || count($rotatedKeys)) {
                throw new \RuntimeException('Unable to load encryption from configuration, missing the private or public key.');
            }

            if (!$privateKeyPath || !$publicKeyPath) {
                throw new \RuntimeException('Unable to load encryption from paths, missing the private or public key path.');
            }

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
                static fn (string $rotatedKeyPath): string => $filesystem->readFile($rotatedKeyPath),
                $rotatedKeyPaths
            );
        }

        $binaryPrivateKey = sodium_hex2bin((string) $privateKey);
        $binaryPublicKey = sodium_hex2bin((string) $publicKey);
        $binaryRotatedKeys = array_map(
            static fn (string $rotatedKey): string => sodium_hex2bin($rotatedKey),
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
