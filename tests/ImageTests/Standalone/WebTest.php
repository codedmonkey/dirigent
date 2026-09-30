<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\ImageTests\Standalone;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;

class WebTest extends DockerStandaloneTestCase
{
    public function testWeb(): void
    {
        $host = $this->container->getHost(); // Use TESTCONTAINERS_HOST_OVERRIDE if the runner returns the wrong host
        $port = $this->container->getMappedPort(7015);
        $client = ScopingHttpClient::forBaseUri(HttpClient::create(), "http://$host:$port/");

        $response = $client->request('GET', '/');
        $content = $response->getContent();

        $this->assertStringContainsString('My Dirigent', $content, 'Accessing the web interface must return a valid response.');
    }
}
