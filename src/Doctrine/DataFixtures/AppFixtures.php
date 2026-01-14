<?php

namespace CodedMonkey\Dirigent\Doctrine\DataFixtures;

use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Entity\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getUsers() as $userData) {
            $user = new User();

            $user->setUsername($userData['username']);
            $user->setName($userData['name']);
            $user->setEmail($userData['email']);
            $user->setRole($userData['role']);
            $user->setPlainPassword($userData['password']);

            $manager->persist($user);
        }

        foreach ($this->getRegistries() as $registryData) {
            $registry = new Registry();

            $registry->setName($registryData['name']);
            $registry->setDescription($registryData['description']);
            $registry->setUrl($registryData['url']);
            $registry->setPackageMirroring($registryData['packageMirroring']);
            $registry->setMirroringPriority($registryData['mirroringPriority']);

            $manager->persist($registry);
        }

        $manager->flush();
    }

    private function getUsers(): \Generator
    {
        yield [
            'username' => 'owner',
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'role' => UserRole::Owner,
            'password' => 'PlainPassword99',
        ];

        yield [
            'username' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
            'password' => 'PlainPassword99',
        ];

        yield [
            'username' => 'user',
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'role' => UserRole::User,
            'password' => 'PlainPassword99',
        ];
    }

    private function getRegistries(): \Generator
    {
        yield [
            'name' => 'Packagist',
            'description' => 'The PHP Package Repository',
            'url' => 'https://repo.packagist.org',
            'packageMirroring' => RegistryPackageMirroring::Manual,
            'mirroringPriority' => 1,
        ];
    }
}
