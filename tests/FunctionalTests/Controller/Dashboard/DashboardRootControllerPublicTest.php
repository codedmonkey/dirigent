<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Tests\FunctionalTests\PublicKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardRootControllerPublicTest extends WebTestCase
{
    #[\Override]
    protected static function getKernelClass(): string
    {
        return PublicKernel::class;
    }

    public function testIndex(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertAnySelectorTextSame('#total_packages .display-6', '1');
    }
}
