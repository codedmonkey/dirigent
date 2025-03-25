<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

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
}
