<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\UserRepository;
use CodedMonkey\Dirigent\Tests\FunctionalTests\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardPackagesInfoControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

    public function testDependents(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('user');
        $client->loginUser($user);

        $client->request('GET', '/?routeName=dashboard_packages_dependents&routeParams[packageName]=psr/log');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('h1 small', 'Dependents');
    }

    public function testImplementations(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('user');
        $client->loginUser($user);

        $client->request('GET', '/?routeName=dashboard_packages_implementations&routeParams[packageName]=psr/log');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('h1 small', 'Implementations');
    }

    public function testProviders(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('user');
        $client->loginUser($user);

        $client->request('GET', '/?routeName=dashboard_packages_providers&routeParams[packageName]=psr/log');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('h1 small', 'Providers');
    }

    public function testSuggesters(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('user');
        $client->loginUser($user);

        $client->request('GET', '/?routeName=dashboard_packages_suggesters&routeParams[packageName]=psr/log');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('h1 small', 'Suggesters');
    }

    public function testStatistics(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/?routeName=dashboard_packages_statistics&routeParams[packageName]=psr/log');

        $this->assertResponseStatusCodeSame(200);

        /** @var PackageRepository $packageRepository */
        $packageRepository = $client->getContainer()->get(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/log');

        $this->assertAnySelectorTextSame('#total_all .display-6', number_format($package->getInstallations()->getTotal(), thousands_separator: ' '));

        $todayKey = (new \DateTimeImmutable())->format('Ymd');
        $this->assertAnySelectorTextSame('#total_today .display-6', number_format($package->getInstallations()->getData()[$todayKey] ?? 0, thousands_separator: ' '));
    }
}
