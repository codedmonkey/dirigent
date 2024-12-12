<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegistryRepository::class)]
class Registry
{
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 1024)]
    private ?string $url = null;

    #[ORM\ManyToOne]
    private ?Credentials $credentials = null;

    #[ORM\Column(type: Types::STRING, enumType: RegistryPackageMirroring::class)]
    private RegistryPackageMirroring|string $packageMirroring = RegistryPackageMirroring::Automatic;

    #[ORM\Column]
    private ?int $mirroringPriority = null;

    #[ORM\Column(nullable: true)]
    private ?\DateInterval $dynamicUpdateDelay = null;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getCredentials(): ?Credentials
    {
        return $this->credentials;
    }

    public function setCredentials(?Credentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    public function getPackageMirroring(): RegistryPackageMirroring|string
    {
        return $this->packageMirroring;
    }

    public function setPackageMirroring(RegistryPackageMirroring|string $packageMirroring): void
    {
        $this->packageMirroring = $packageMirroring;
    }

    public function getMirroringPriority(): ?string
    {
        return $this->mirroringPriority;
    }

    public function setMirroringPriority(?string $mirroringPriority): void
    {
        $this->mirroringPriority = $mirroringPriority;
    }

    public function getDynamicUpdateDelay(): ?\DateInterval
    {
        return $this->dynamicUpdateDelay;
    }

    public function setDynamicUpdateDelay(?\DateInterval $dynamicUpdateDelay): void
    {
        $this->dynamicUpdateDelay = $dynamicUpdateDelay;
    }

    public function getDomain(): string
    {
        return parse_url($this->url, PHP_URL_HOST);
    }
}
