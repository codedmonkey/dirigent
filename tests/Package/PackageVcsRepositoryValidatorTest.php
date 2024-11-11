<?php

namespace CodedMonkey\Conductor\Tests\Package;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Package\PackageVcsRepositoryValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PackageVcsRepositoryValidatorTest extends TestCase
{
    public static function invalidUrlProvider(): array
    {
        return [
            [
                'Local filesystem repositories are not allowed',
                [
                    './foo',
                    'C:/bar',
                    '/baz',
                ],
            ],
            [
                'Passing credentials in the repository URL is not allowed, create credentials first',
                [
                    'http://foo@example.com',
                    'https://foo:bar@example.com',
                ],
            ],
            [
                'Invalid repository URL',
                [
                    'foo bar',
                    'baz',
                    '%FOO',
                    '$BAR',
                ],
            ],
        ];
    }

    #[DataProvider('invalidUrlProvider')]
    public function testInvalidUrls(string $error, array $urls): void
    {
        $validator = new PackageVcsRepositoryValidator();

        foreach ($urls as $url) {
            $package = new Package();
            $package->setRepositoryUrl($url);

            $result = $validator->validate($package);

            $this->assertSame(['error' => $error], $result);
        }
    }
}
