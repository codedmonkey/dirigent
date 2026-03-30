<?php

namespace CodedMonkey\Dirigent\Doctrine\Middleware;

use CodedMonkey\Dirigent\Doctrine\Type\EncryptedTextType;
use CodedMonkey\Dirigent\Encryption\Encryption;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Types\Type;

/**
 * Configures EncryptedTextType by manually injecting the encryption utility after initializing Doctrine.
 */
#[AsMiddleware]
readonly class EncryptionMiddleware implements Middleware
{
    public function __construct(private Encryption $encryption)
    {
    }

    public function wrap(Driver $driver): Driver
    {
        return new class($driver, $this->encryption) extends AbstractDriverMiddleware {
            public function __construct(Driver $driver, private readonly Encryption $encryption)
            {
                parent::__construct($driver);
            }

            public function connect(array $params): Connection
            {
                /** @var EncryptedTextType $doctrineType */
                $doctrineType = Type::getType(EncryptedTextType::TYPE);
                $doctrineType->setEncryptionUtility($this->encryption);

                return parent::connect($params);
            }
        };
    }
}
