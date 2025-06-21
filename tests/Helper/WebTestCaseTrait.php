<?php

namespace CodedMonkey\Dirigent\Tests\Helper;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait WebTestCaseTrait
{
    /**
     * @template TServiceClass of object
     *
     * @param class-string<TServiceClass> $class
     *
     * @return TServiceClass
     */
    protected function getService(string $class, ?string $name = null): object
    {
        /** @var KernelBrowser $client */
        $client = static::getClient();

        return $client->getContainer()->get($name ?: $class);
    }

    protected function loginUser(string $username = 'user'): User
    {
        /** @var KernelBrowser $client */
        $client = static::getClient();
        $userRepository = static::getService(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername($username);
        $client->loginUser($user);

        return $user;
    }
}
