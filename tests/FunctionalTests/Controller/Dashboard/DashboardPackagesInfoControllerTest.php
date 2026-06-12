<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardPackagesInfoControllerTest extends WebTestCase
{
    use EntityManagerTestTrait;
    use MockEntityFactoryTrait;
    use WebTestCaseTrait;

    public function testInfo(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testInfoRedirectsWithNoVersions(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $package = $this->createMockPackage();
        $this->persistEntities($package);

        $client->request('GET', "/packages/{$package->getName()}");

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertSame(sprintf('/packages/%s/versions', $package->getName()), $client->getRequest()->getRequestUri());
    }

    public function testPackageInfo(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/versions/1.0.0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPackageMetadataList(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/packages/psr/log/revisions/1.0.0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPinMetadata(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        $latestMetadata = $this->createMockMetadata($version);
        $version->setCurrentMetadata($latestMetadata);
        $this->persistEntities($package, $version, $metadata, $latestMetadata);

        $client->request('GET', sprintf('/packages/%s/versions/%s?revision=%d', $package->getName(), $version->getName(), $metadata->getRevision()));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Pin revision');

        $this->assertResponseRedirects(sprintf('/packages/%s/versions/%s', $package->getName(), $version->getName()));

        $this->clearEntities();

        $savedVersion = $this->findEntity(Version::class, $version->getId());

        $this->assertTrue($savedVersion->isPinned(), 'The version is pinned.');
        $this->assertSame($metadata->getRevision(), $savedVersion->getCurrentMetadata()->getRevision(), 'The pinned revision is the current metadata.');
    }

    public function testUnpinMetadata(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        $latestMetadata = $this->createMockMetadata($version);
        $version->setPinned(true);
        $this->persistEntities($package, $version, $metadata, $latestMetadata);

        $client->request('GET', sprintf('/packages/%s/versions/%s', $package->getName(), $version->getName()));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Unpin revision');

        $this->assertResponseRedirects(sprintf('/packages/%s/versions/%s', $package->getName(), $version->getName()));

        $this->clearEntities();

        $savedVersion = $this->findEntity(Version::class, $version->getId());

        $this->assertFalse($savedVersion->isPinned(), 'The version is no longer pinned.');
        $this->assertSame($latestMetadata->getRevision(), $savedVersion->getCurrentMetadata()->getRevision(), 'The latest revision is the current metadata.');
    }

    public function testPinMetadataWithUnknownRevision(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        $latestMetadata = $this->createMockMetadata($version);
        $version->setCurrentMetadata($latestMetadata);
        $this->persistEntities($package, $version, $metadata, $latestMetadata);

        $client->request('GET', sprintf('/packages/%s/versions/%s', $package->getName(), $version->getName()));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->submitForm('Pin revision', [
            'revision' => '999',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPinMetadataWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $this->loginUser('admin');

        $mockEntities = $this->createMockPackageWithMetadata();
        $this->persistEntities(...$mockEntities);

        [$package, $version] = $mockEntities;

        $client->request('POST', sprintf('/packages/%s/pin-metadata/%s', $package->getName(), $version->getName()), [
            'action' => 'pin',
            'revision' => '1',
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testPinMetadataRequiresAdminRole(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $mockEntities = $this->createMockPackageWithMetadata();
        $this->persistEntities(...$mockEntities);

        [$package, $version] = $mockEntities;

        $client->request('POST', sprintf('/packages/%s/pin-metadata/%s', $package->getName(), $version->getName()), [
            'action' => 'pin',
            'revision' => '1',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
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

        $todayKey = new \DateTimeImmutable()->format('Ymd');
        $this->assertAnySelectorTextSame('#total_today .display-6', number_format($package->getInstallations()->getData()[$todayKey] ?? 0, thousands_separator: ' '));
    }
}
