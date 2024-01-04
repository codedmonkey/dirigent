<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\AccessTokenRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Security\Core\User\UserInterface;

#[Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken
{
    #[Column]
    #[GeneratedValue]
    #[Id]
    public ?int $id = null;

    #[ManyToOne(User::class)]
    public ?UserInterface $user = null;

    #[Column]
    public ?string $name = null;

    #[Column]
    public readonly string $token;

    #[Column]
    public readonly \DateTimeImmutable $createdAt;

    #[Column(nullable: true)]
    public ?\DateTimeImmutable $expiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->token = uniqid('conductor-');
    }

    public function isValid(): bool
    {
        return true;
    }
}
