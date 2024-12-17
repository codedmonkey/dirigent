<?php

namespace CodedMonkey\Dirigent\Tests\Controller;

use CodedMonkey\Dirigent\Tests\PublicKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class ApiControllerTest extends KernelTestCase
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

    private function requestJson(...$requestArguments)
    {
        $request = Request::create(...$requestArguments);
        $response = self::$kernel->handle($request);

        return json_decode($response->getContent(), true);
    }
}
