<?php

namespace CodedMonkey\Conductor\Tests\Package;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Package\PackageVcsRepositoryValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PackageVcsRepositoryValidatorTest extends TestCase
{
    public static function invalidLocalFilesystemUrlProvider(): array
    {
        return [
            ['./foo'],
            ['C:/bar'],
            ['/acme'],
        ];
    }

    #[DataProvider('invalidLocalFilesystemUrlProvider')]
    public function testInvalidLocalFilesystemUrls(string $url): void
    {
        $package = new Package();
        $package->setRepositoryUrl($url);

        $validator = new PackageVcsRepositoryValidator();
        $result = $validator->validate($package);

        $this->assertSame(['error' => 'Local filesystem repositories are not allowed'], $result);
    }

    public static function invalidAuthUrlProvider(): array
    {
        return [
            ['http://foo@example.com'],
            ['https://foo:bar@example.com'],
        ];
    }

    #[DataProvider('invalidAuthUrlProvider')]
    public function testInvalidAuthUrls(string $url): void
    {
        $package = new Package();
        $package->setRepositoryUrl($url);

        $validator = new PackageVcsRepositoryValidator();
        $result = $validator->validate($package);

        $this->assertSame(['error' => 'Passing credentials in the repository URL is not allowed, create credentials first'], $result);
    }
}
