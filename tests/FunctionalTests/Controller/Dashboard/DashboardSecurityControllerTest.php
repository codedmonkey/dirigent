<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardSecurityControllerTest extends WebTestCase
{
    use WebTestCaseTrait;

    /**
     * Verify the homepage redirects to the login page when the application is not publicly accessible.
     */
    public function testLoginRedirect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);
    }
}
