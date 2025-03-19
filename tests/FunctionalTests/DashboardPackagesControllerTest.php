<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardPackagesControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

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

    public function testAddVcsRepository(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

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
        $this->loginUser('admin');

        $client->request('GET', '/?routeName=dashboard_packages_edit&routeParams[packageName]=psr/log');
        $client->submitForm('Save changes');

        $this->assertResponseStatusCodeSame(302);
    }
}
