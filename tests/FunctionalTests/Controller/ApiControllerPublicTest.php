<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Dirigent\Tests\FunctionalTests\PublicKernel;
use CodedMonkey\Dirigent\Tests\Helper\KernelTestCaseTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerPublicTest extends KernelTestCase
{
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

    public function testPackagMetadata(): void
    {
        self::bootKernel();

        $packageData = $this->requestJson('/p2/psr/log.json', 'GET');

        $this->assertNotSame([], $packageData);
    }

    public function testPackageMetadataDev(): void
    {
        self::bootKernel();

        $packageData = $this->requestJson('/p2/psr/log~dev.json', 'GET');

        $this->assertNotSame([], $packageData);
    }

    public function testPackageMetadataIsNotFound(): void
    {
        self::bootKernel();

        $request = Request::create('/p2/psr/container.json', 'GET');
        $response = self::$kernel->handle($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
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

        $this->assertNotSame([], $packageData);
    }

    private function requestJson(...$requestArguments)
    {
        $request = Request::create(...$requestArguments);
        $response = self::$kernel->handle($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        return json_decode($response->getContent(), true);
    }
}
