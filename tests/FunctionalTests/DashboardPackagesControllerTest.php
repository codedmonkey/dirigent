<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardPackagesControllerTest extends WebTestCase
{
    public function testStatistics(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('user');
        $client->loginUser($user);

        $client->request('GET', '/?routeName=dashboard_packages_statistics&routeParams[packageName]=psr/log');

        $this->assertResponseStatusCodeSame(200);

        /** @var PackageRepository $packageRepository */
        $packageRepository = $client->getContainer()->get(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/log');

        $this->assertAnySelectorTextSame('#total_all .display-6', number_format($package->getInstallations()->getTotal(), thousands_separator: ' '));

        $todayKey = (new \DateTimeImmutable())->format('Ymd');
        $this->assertAnySelectorTextSame('#total_today .display-6', number_format($package->getInstallations()->getData()[$todayKey] ?? 0, thousands_separator: ' '));
    }

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

    public function testAddMirror(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('owner');
        $client->loginUser($user);

        $registry = $client->getContainer()->get(RegistryRepository::class)->findOneBy(['name' => 'Packagist']);

        $client->request('GET', '/?routeName=dashboard_packages_add_mirroring');
        $client->submitForm('Add packages', [
            'package_add_mirroring_form[packages]' => 'psr/cache',
            'package_add_mirroring_form[registry]' => $registry->getId(),
        ]);

        $this->assertResponseStatusCodeSame(200);

        // $this->assertAnySelectorTextSame(
        //     '.text-success',
        //     'The package psr/cache was created successfully.',
        //     'A message showing the package was created must be shown.',
        // );

        /** @var PackageRepository $packageRepository */
        $packageRepository = $client->getContainer()->get(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/cache');
        self::assertNotNull($package, 'A package was created.');

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
