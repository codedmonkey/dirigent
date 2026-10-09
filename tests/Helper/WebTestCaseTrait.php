<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\Helper;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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

    protected function createCsrfToken(string $name): string
    {
        /** @var KernelBrowser $client */
        $client = static::getClient();
        $session = $client->getSession();

        $request = new Request();
        $request->setSession($session);

        $requestStack = self::getService(RequestStack::class);
        $requestStack->push($request);

        $csrfToken = self::getService(CsrfTokenManagerInterface::class)->getToken($name)->getValue();

        $session->save();
        $requestStack->pop();

        return $csrfToken;
    }
}
