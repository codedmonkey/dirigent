<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\AccessTokenRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken
{
    #[Column]
    #[GeneratedValue]
    #[Id]
    private ?int $id = null;

    #[ManyToOne(User::class)]
    private ?User $user = null;

    #[Column]
    private ?string $name = null;

    #[Column]
    private readonly string $token;

    #[Column]
    private readonly \DateTimeImmutable $createdAt;

    #[Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        // todo generate proper hash
        $this->token = uniqid('conductor-');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isValid(): bool
    {
        // todo check if expired
        return true;
    }
}
