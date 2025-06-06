<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Tests\FunctionalTests\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardPackagesInfoControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

    public function testInfo(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testPackageInfo(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/v/1.0.0');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testVersions(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/versions');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testStatistics(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/statistics');

        $this->assertResponseStatusCodeSame(200);

        /** @var PackageRepository $packageRepository */
        $packageRepository = $client->getContainer()->get(PackageRepository::class);

        $package = $packageRepository->findOneByName('psr/log');

        $this->assertAnySelectorTextSame('#total_all .display-6', number_format($package->getInstallations()->getTotal(), thousands_separator: ' '));

        $todayKey = (new \DateTimeImmutable())->format('Ymd');
        $this->assertAnySelectorTextSame('#total_today .display-6', number_format($package->getInstallations()->getData()[$todayKey] ?? 0, thousands_separator: ' '));
    }
}
