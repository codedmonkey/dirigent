<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use CodedMonkey\Conductor\Doctrine\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
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
        // $this->plainPassword = null;
    }
}
