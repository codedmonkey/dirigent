<?php

namespace CodedMonkey\Dirigent\Composer;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Composer\Config;
use Composer\Factory;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\Locker;
use Composer\Repository\ComposerRepository;
use Composer\Repository\VcsRepository;
use Composer\Util\HttpDownloader;
use Composer\Util\Loop;
use Composer\Util\ProcessExecutor;

class ComposerClient
{
    public function createComposerRepository(Package|Registry $registry, ?IOInterface $io = null, ?Config $config = null): ComposerRepository
    {
        $registry = $registry instanceof Package ? $registry->getMirrorRegistry() : $registry;

        $config ??= ConfigFactory::createForRegistry($registry);
        $io ??= $this->createIo($io);
        $httpDownloader = $this->createHttpDownloader($io, $config);

        return new ComposerRepository(['url' => $registry->getUrl()], $io, $config, $httpDownloader);
    }

    public function createVcsRepository(Package $package, ?IOInterface $io = null, ?Config $config = null): VcsRepository
    {
        $repoUrl = $package->getRepositoryUrl();

        $config ??= ConfigFactory::createForVcsRepository($repoUrl, $package->getRepositoryCredentials());
        $io ??= $this->createIo($io);
        $httpDownloader = $this->createHttpDownloader($io, $config);

        return new VcsRepository(['url' => $repoUrl], $io, $config, $httpDownloader);
    }

    public function createHttpDownloader(?IOInterface $io = null, ?Config $config = null): HttpDownloader
    {
        $config ??= Factory::createConfig();
        $io ??= $this->createIo($io);

        return new HttpDownloader($io, $config, self::getHttpDownloaderOptions());
    }

    public function createIo(?Config $config = null): IOInterface
    {
        $io = new NullIO();
        $io->loadConfiguration($config);

        return $io;
    }

    public function createLocker(Version $version, ?IOInterface $io = null, ?Config $config = null): ?Locker
    {
        $package = $version->getPackage();

        $config ??= ConfigFactory::createForVcsRepository($package->getRepositoryUrl(), $package->getRepositoryCredentials());
        $io ??= $this->createIo($config);
        $repository = $this->createVcsRepository($package, $io, $config);
        $driver = $repository->getDriver();
        $process = $this->createProcessExecutor($io);
        $httpDownloader = $this->createHttpDownloader($io, $config);
        $loop = new Loop($httpDownloader, $process);
        $installationManager = new InstallationManager($loop, $io);

        $jsonSource = $driver->getFileContent('composer.json', $version->getSource()['reference']);
        $lockerSource = $driver->getFileContent('composer.lock', $version->getSource()['reference']);

        if (empty($lockerSource)) {
            return null;
        }

        $lockerJsonFile = new InMemoryJsonFile($lockerSource);

        return new Locker($io, $lockerJsonFile, $installationManager, $jsonSource);
    }

    public function createProcessExecutor(?IOInterface $io = null): ProcessExecutor
    {
        $io ??= $this->createIo($io);

        return new ProcessExecutor($io);
    }

    public static function getHttpDownloaderOptions(): array
    {
        $options['http']['header'][] = 'User-Agent: Dirigent (https://github.com/codedmonkey/dirigent)';
        $options['max_file_size'] = 128_000_000;

        return $options;
    }
}
