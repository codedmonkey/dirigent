<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\KeywordRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use Composer\Package\CompletePackage;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(PackageMetadataResolver::class)]
class PackageMetadataResolverTest extends TestCase
{
    public function testReadmeDoesNotRenderExecutableHtml(): void
    {
        $readme = $this->resolveReadme('<img src="x" onerror="alert(1)">');

        self::assertStringNotContainsString('<img', $readme);
    }

    public static function unsafeMarkdownLinks(): iterable
    {
        yield 'JavaScript link' => ['[unsafe](javascript:alert%281%29)', 'href="javascript:'];
        yield 'data image' => ['![unsafe](data:image/svg+xml;base64,PHN2Zz4=)', 'src="data:'];
    }

    #[DataProvider('unsafeMarkdownLinks')]
    public function testReadmeDoesNotRenderUnsafeMarkdownUrls(string $source, string $unsafeAttribute): void
    {
        self::assertStringNotContainsString($unsafeAttribute, $this->resolveReadme($source));
    }

    public function testReadmePreservesSafeGithubFlavoredMarkdown(): void
    {
        $readme = $this->resolveReadme(<<<'MARKDOWN'
            # Readme

            **strong** and ~~removed~~

            | Package | Version |
            | --- | --- |
            | example | 1.0 |

            [website](https://example.com/docs)
            ![badge](https://example.com/badge.png)

            ```html
            <img src="x" onerror="alert(1)">
            ```
            MARKDOWN);

        self::assertStringContainsString('<h1>Readme</h1>', $readme);
        self::assertStringContainsString('<strong>strong</strong>', $readme);
        self::assertStringContainsString('<del>removed</del>', $readme);
        self::assertStringContainsString('<table>', $readme);
        self::assertStringContainsString('href="https://example.com/docs"', $readme);
        self::assertStringContainsString('src="https://example.com/badge.png"', $readme);
        self::assertStringContainsString('&lt;img', $readme);
        self::assertStringNotContainsString('<img src="x"', $readme);
    }

    public function testPlainTextReadmeRemainsEscaped(): void
    {
        $source = '<img src="x" onerror="alert(1)"> & plain text';

        self::assertSame('<pre>' . htmlspecialchars($source) . '</pre>', $this->resolveReadme($source, 'README.txt'));
    }

    private function resolveReadme(string $source, string $filename = 'README.md'): string
    {
        $package = new Package('vendor/readme');
        $package->setFetchStrategy(PackageFetchStrategy::Vcs);
        $package->setRepositoryUrl('https://example.com/vendor/readme.git');
        new \ReflectionProperty(Package::class, 'id')->setValue($package, 1);

        $data = new CompletePackage('vendor/readme', '1.0.0.0', '1.0.0');
        $data->setSourceType('git');
        $data->setSourceUrl($package->getRepositoryUrl());
        $data->setSourceReference('reference');

        $driver = $this->createStub(VcsDriverInterface::class);
        $driver->method('getRootIdentifier')->willReturn('reference');
        $driver->method('getComposerInformation')->willReturn(['name' => 'vendor/readme', 'readme' => $filename]);
        $driver->method('getFileContent')->willReturn($source);

        $repository = $this->createStub(VcsRepository::class);
        $repository->method('getDriver')->willReturn($driver);
        $repository->method('findPackages')->willReturn([$data]);

        $composer = $this->createStub(ComposerClient::class);
        $composer->method('createVcsRepository')->willReturn($repository);

        $messenger = $this->createStub(MessageBusInterface::class);
        $messenger->method('dispatch')->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $resolver = new PackageMetadataResolver(
            $composer,
            $messenger,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(KeywordRepository::class),
            $this->createStub(RegistryRepository::class),
            $this->createStub(PackageRepository::class),
            false,
            false,
            false,
            false,
        );
        $resolver->resolve($package);

        return $package->getVersions()->first()->getCurrentMetadata()->getReadme();
    }
}
