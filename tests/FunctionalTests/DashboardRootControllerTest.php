<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardRootControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('user');
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('#total_packages .display-6', '1');
    }
}
