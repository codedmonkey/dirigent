<?php

namespace CodedMonkey\Dirigent\Doctrine\Type;

use CodedMonkey\Dirigent\Encryption\Encryption;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EncryptedTextType extends Type
{
    public const TYPE = 'encrypted_text';

    private ?Encryption $encryption = null;

    public function setEncryptionUtility(Encryption $encryption): void
    {
        $this->encryption = $encryption;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // Use LONGTEXT column
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        return $this->encryption->reveal($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        return $this->encryption->seal($value);
    }
}
