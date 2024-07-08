<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\RegistryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity(repositoryClass: RegistryRepository::class)]
class Registry
{
    #[Column]
    #[GeneratedValue]
    #[Id]
    public ?int $id = null;

    #[Column]
    public ?string $name = null;

    #[Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[Column(length: 1024)]
    public ?string $url = null;

    #[ManyToOne]
    private ?Credentials $credentials = null;

    #[Column(type: Types::STRING, enumType: RegistryPackageMirroring::class)]
    public RegistryPackageMirroring|string $packageMirroring = RegistryPackageMirroring::Automatic;

    #[Column]
    public ?int $mirroringPriority = null;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCredentials(): ?Credentials
    {
        return $this->credentials;
    }

    public function setCredentials(?Credentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    public function getDomain(): string
    {
        return parse_url($this->url, PHP_URL_HOST);
    }
}
