<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use CodedMonkey\Dirigent\Tests\PublicKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardRootControllerPublicTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return PublicKernel::class;
    }

    public function testIndex(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('#total_packages .display-6', '1');
    }
}
