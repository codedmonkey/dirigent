<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller;

use CodedMonkey\Dirigent\Tests\FunctionalTests\PublicKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerPublicTest extends KernelTestCase
{
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

    public function testPackage(): void
    {
        self::bootKernel();

        $packageData = $this->requestJson('/p2/psr/log.json', 'GET');

        $this->assertNotSame([], $packageData);
    }

    public function testPackageDev(): void
    {
        self::bootKernel();

        $packageData = $this->requestJson('/p2/psr/log~dev.json', 'GET');

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
