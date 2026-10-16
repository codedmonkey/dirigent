<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Entity\RegistryPackageMirroring;
use CodedMonkey\Dirigent\Package\PackageProviderManager;
use CodedMonkey\Dirigent\Tests\FunctionalTests\PublicKernel;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\KernelTestCaseTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerPublicTest extends KernelTestCase
{
    use EntityManagerTestTrait;
    use KernelTestCaseTrait;
    use MockEntityFactoryTrait;

    #[\Override]
    protected static function getKernelClass(): string
    {
        return PublicKernel::class;
    }

    public function testRoot(): void
    {
        self::bootKernel();

        $rootData = $this->requestJson('/packages.json', 'GET');

        $this->assertSame([
            'packages' => [],
            'metadata-url' => '/p2/%package%.json',
            'notify-batch' => '/downloads',
        ], $rootData);
    }

    public function testPackageMetadata(): void
    {
        self::bootKernel();

        $package = $this->createPackageWithProvider();
        $packageName = $package->getName();

        $packageData = $this->requestJson("/p2/$packageName.json", 'GET');

        $this->assertArrayHasKey('packages', $packageData);
        $this->assertArrayHasKey($packageName, $packageData['packages']);
        $this->assertCount(1, $packageData['packages'][$packageName]);
        $this->assertSame('1.0.0', $packageData['packages'][$packageName][0]['version']);
    }

    public function testPackageMetadataDev(): void
    {
        self::bootKernel();

        $package = $this->createPackageWithProvider();
        $packageName = $package->getName();

        $packageData = $this->requestJson("/p2/$packageName~dev.json", 'GET');

        $this->assertArrayHasKey('packages', $packageData);
        $this->assertArrayHasKey($packageName, $packageData['packages']);
        $this->assertCount(1, $packageData['packages'][$packageName]);
        $this->assertSame('dev-main', $packageData['packages'][$packageName][0]['version']);
    }

    public function testPackageMetadataIsNotFound(): void
    {
        self::bootKernel();

        $connection = $this->getService(EntityManagerInterface::class)->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel();

        $request = Request::create('/p2/psr/container.json', 'GET');
        $response = self::$kernel->handle($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame($transactionNestingLevel, $connection->getTransactionNestingLevel());
    }

    public function testPackageMetadataIsMirroredOnRequest(): void
    {
        self::bootKernel();

        // Verify the package doesn't already exist
        $package = $this->findEntity(Package::class, ['name' => 'psr/container']);

        $this->assertNull($package);

        // Update the registry so that it allows dynamically adding packages on request from the API
        $registry = $this->findEntity(Registry::class, ['name' => 'Packagist']);
        $registry->setPackageMirroring(RegistryPackageMirroring::Automatic);

        $this->persistEntities($registry);

        // Execute the API endpoint
        $packageData = $this->requestJson('/p2/psr/container.json', 'GET');

        $this->assertArrayHasKey('packages', $packageData);
        $this->assertArrayHasKey('psr/container', $packageData['packages']);
    }

    private function createPackageWithProvider(): Package
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        $devVersion = $this->createMockVersion($package, 'dev-main', true);
        $devMetadata = $this->createMockMetadata($devVersion);
        $devVersion->setCurrentMetadata($devMetadata);

        // Serve the generated provider without triggering a remote metadata update.
        $package->setUpdatedAt(new \DateTimeImmutable());
        $this->persistEntities($package, $version, $metadata, $devVersion, $devMetadata);

        $this->getService(PackageProviderManager::class)->dump($package);

        return $package;
    }

    private function requestJson(...$requestArguments)
    {
        $request = Request::create(...$requestArguments);
        $response = self::$kernel->handle($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response instanceof BinaryFileResponse
            ? $response->getFile()->getContent()
            : $response->getContent();

        $this->assertNotFalse($content);

        return json_decode($content, true);
    }
}
