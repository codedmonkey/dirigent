<?php

namespace CodedMonkey\Conductor\Doctrine\EventListener;

use CodedMonkey\Conductor\Doctrine\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(Events::prePersist, entity: User::class)]
#[AsEntityListener(Events::preUpdate, entity: User::class)]
readonly class UserListener
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function prePersist(User $user): void
    {
        if (null === $user->getPlainPassword()) {
            throw new \LogicException('A new user can\'t be created without a password.');
        }

        $this->hashPassword($user);
    }

    public function preUpdate(User $user): void
    {
        if (null !== $user->getPlainPassword()) {
            $this->hashPassword($user);
        }
    }

    private function hashPassword(User $user): void
    {
        $password = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($password);

        $user->eraseCredentials();
    }
}
