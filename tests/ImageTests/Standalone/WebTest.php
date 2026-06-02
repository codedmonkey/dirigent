<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\ImageTests\Standalone;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;

class WebTest extends DockerStandaloneTestCase
{
    public function testWeb(): void
    {
        $mappedPort = $this->container->getMappedPort(7015);
        $client = ScopingHttpClient::forBaseUri(HttpClient::create(), "http://localhost:$mappedPort/");

        $response = $client->request('GET', '/');
        $content = $response->getContent();

        $this->assertStringContainsString('My Dirigent', $content, 'Accessing the web interface must return a valid response.');
    }
}
