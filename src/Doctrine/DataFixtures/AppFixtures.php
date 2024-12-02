<?php

namespace CodedMonkey\Conductor\Doctrine\DataFixtures;

use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Conductor\Doctrine\Entity\User;
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
            $user->setRoles($userData['roles']);
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
            'roles' => ['ROLE_SUPER_ADMIN'],
            'password' => 'PlainPassword99',
        ];

        yield [
            'username' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
            'password' => 'PlainPassword99',
        ];

        yield [
            'username' => 'user',
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'roles' => [],
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
