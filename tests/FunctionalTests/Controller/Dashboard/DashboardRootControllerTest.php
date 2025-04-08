<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Kernel;
use CodedMonkey\Dirigent\Tests\FunctionalTests\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardRootControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('#total_packages .display-6', '1');
    }

    public function testCredits(): void
    {
        $client = static::createClient();
        $this->loginUser();

        $client->request('GET', '/credits');

        $this->assertResponseStatusCodeSame(200);

        $this->assertAnySelectorTextSame('.list-group-item div', 'v' . Kernel::VERSION);
    }
}
