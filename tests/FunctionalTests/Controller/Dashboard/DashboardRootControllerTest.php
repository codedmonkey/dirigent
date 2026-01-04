<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Kernel;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardRootControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertAnySelectorTextSame('#total_packages .display-6', '2');
    }

    public function testCredits(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/credits');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertAnySelectorTextSame('.list-group-item div', 'v' . Kernel::VERSION);
    }
}
