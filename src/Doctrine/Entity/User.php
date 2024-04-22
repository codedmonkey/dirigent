<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\UserRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Entity(repositoryClass: UserRepository::class)]
#[Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Column]
    #[GeneratedValue]
    #[Id]
    public ?int $id = null;

    #[Column(length: 255)]
    public ?string $name = null;

    #[Column(length: 255, unique: true)]
    public ?string $email = null;

    #[Column]
    private array $roles = [];

    #[Column]
    public ?string $password = null;

    public ?string $plainPassword = null;

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles) || in_array('ROLE_SUPER_ADMIN', $this->roles);
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->roles);
    }

    public function setAdmin(bool $admin): void
    {
        if ($admin) {
            if (!in_array('ROLE_ADMIN', $this->roles)) {
                $this->roles[] = 'ROLE_ADMIN';
            }
        } else {
            if (false !== $key = array_search('ROLE_ADMIN', $this->roles)) {
                unset($this->roles[$key]);
            }
        }
    }

    public function setSuperAdmin(bool $admin): void
    {
        if ($admin) {
            if (!in_array('ROLE_SUPER_ADMIN', $this->roles)) {
                $this->roles[] = 'ROLE_SUPER_ADMIN';
            }
        } else {
            if (false !== $key = array_search('ROLE_SUPER_ADMIN', $this->roles)) {
                unset($this->roles[$key]);
            }
        }
    }

    public function setPlainPassword(string $password): self
    {
        $this->plainPassword = $password;
        $this->password = null;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }
}
