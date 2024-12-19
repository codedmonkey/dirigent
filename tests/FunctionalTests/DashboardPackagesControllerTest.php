<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardPackagesControllerTest extends WebTestCase
{
    public function testAddVcsRepository(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('owner');
        $client->loginUser($user);

        $client->request('GET', '/?routeName=dashboard_packages_add_vcs');
        $client->submitForm('Add VCS repository', [
            'package_add_vcs_form[repositoryUrl]' => 'https://github.com/php-fig/container',
        ]);

        $this->assertResponseStatusCodeSame(302);

        /** @var PackageRepository $packageRepository */
        $packageRepository = $client->getContainer()->get(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/container');
        self::assertNotNull($package, 'A package was created');

        $packageRepository->remove($package, true);
    }

    public function testEdit(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('owner');
        $client->loginUser($user);

        $client->request('GET', '/?routeName=dashboard_packages_edit&routeParams[packageName]=psr/log');
        $client->submitForm('Save changes');

        $this->assertResponseStatusCodeSame(302);
    }
}
