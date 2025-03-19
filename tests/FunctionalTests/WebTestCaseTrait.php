<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait WebTestCaseTrait
{
    protected function loginUser(string $username = 'user'): User
    {
        /** @var KernelBrowser $client */
        $client = static::getClient();
        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername($username);
        $client->loginUser($user);

        return $user;
    }
}
