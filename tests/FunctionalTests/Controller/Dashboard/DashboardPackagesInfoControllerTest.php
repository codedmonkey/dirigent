<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardPackagesInfoControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

    public function testInfo(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPackageInfo(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/versions/1.0.0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testVersions(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/versions');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testDependents(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/dependents');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertAnySelectorTextSame('h1 small', 'Dependents');
    }

    public function testImplementations(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/implementations');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertAnySelectorTextSame('h1 small', 'Implementations');
    }

    public function testProviders(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/providers');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertAnySelectorTextSame('h1 small', 'Providers');
    }

    public function testSuggesters(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/suggesters');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertAnySelectorTextSame('h1 small', 'Suggesters');
    }

    public function testStatistics(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/statistics');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $package = self::getService(PackageRepository::class)->findOneByName('psr/log');

        $this->assertAnySelectorTextSame('#total_all .display-6', number_format($package->getInstallations()->getTotal(), thousands_separator: ' '));

        $todayKey = (new \DateTimeImmutable())->format('Ymd');
        $this->assertAnySelectorTextSame('#total_today .display-6', number_format($package->getInstallations()->getData()[$todayKey] ?? 0, thousands_separator: ' '));
    }
}
