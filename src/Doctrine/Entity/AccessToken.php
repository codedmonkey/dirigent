<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\AccessTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken extends TrackedEntity
{
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[ORM\ManyToOne(User::class)]
    private ?User $user = null;

    #[ORM\Column]
    private ?string $name = null;

    #[ORM\Column]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    private ?string $plainToken = null;

    public function __construct()
    {
        $this->plainToken = uniqid('dirigent-');
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

    public function getToken(): ?string
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

    public function getPlainToken(): ?string
    {
        return $this->plainToken;
    }

    public function isValid(): bool
    {
        return !$this->expiresAt || $this->expiresAt->getTimestamp() <= time();
    }

    public function hashCredentials(string $token): void
    {
        if (null === $this->plainToken) {
            throw new \LogicException('Access token was already hashed.');
        }

        $this->token = $token;
        $this->plainToken = null;
    }
}
