<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Entity(repositoryClass: UserRepository::class)]
#[Table(name: '`user`')]
#[UniqueEntity('username', message: 'This username is already taken')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Column]
    #[GeneratedValue]
    #[Id]
    private ?int $id = null;

    #[Column(length: 80, unique: true)]
    private ?string $username = null;

    #[Column(length: 180, nullable: true)]
    private ?string $name = null;

    #[Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[Column]
    private array $roles = [];

    #[Column]
    private ?string $password = null;

    private ?string $plainPassword = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $password): self
    {
        $this->plainPassword = $password;
        $this->password = null;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true) || in_array('ROLE_SUPER_ADMIN', $this->roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->roles, true);
    }

    public function setAdmin(bool $admin): void
    {
        if ($admin) {
            if (!in_array('ROLE_ADMIN', $this->roles, true)) {
                $this->roles[] = 'ROLE_ADMIN';
            }
        } else {
            if (false !== $key = array_search('ROLE_ADMIN', $this->roles, true)) {
                unset($this->roles[$key]);
            }
        }
    }

    public function setSuperAdmin(bool $admin): void
    {
        if ($admin) {
            if (!in_array('ROLE_SUPER_ADMIN', $this->roles, true)) {
                $this->roles[] = 'ROLE_SUPER_ADMIN';
            }
        } else {
            if (false !== $key = array_search('ROLE_SUPER_ADMIN', $this->roles, true)) {
                unset($this->roles[$key]);
            }
        }
    }
}
