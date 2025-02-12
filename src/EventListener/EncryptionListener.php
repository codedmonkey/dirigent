<?php

namespace CodedMonkey\Dirigent\EventListener;

use CodedMonkey\Dirigent\Doctrine\Type\EncryptedTextType;
use CodedMonkey\Dirigent\Encryption\Encryption;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

readonly class EncryptionListener
{
    public function __construct(
        private Connection $connection,
        private Encryption $encryption,
    ) {
    }

    #[AsEventListener(RequestEvent::class)]
    public function configureDoctrineEncryptedTextType(): void
    {
        /** @var EncryptedTextType $doctrineType */
        $doctrineType = Type::getType(EncryptedTextType::TYPE);

        $doctrineType->setEncryptionUtility($this->encryption);
    }
}
