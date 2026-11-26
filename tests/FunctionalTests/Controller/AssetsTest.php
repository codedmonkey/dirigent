<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller;

use CodedMonkey\Dirigent\Tests\FunctionalTests\PublicKernel;
use CodedMonkey\Dirigent\Tests\Helper\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AssetsTest extends WebTestCase
{
    use WebTestCaseTrait;

    #[\Override]
    protected static function getKernelClass(): string
    {
        return PublicKernel::class;
    }

    public function testAssets(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertSelectorExists('link[rel="stylesheet"][href^="/build/dashboard-"]');
        $this->assertSelectorExists('script[type="module"][src^="/build/dashboard-"]');
    }
}
