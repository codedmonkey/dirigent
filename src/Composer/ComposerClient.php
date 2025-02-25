<?php

namespace CodedMonkey\Dirigent\Composer;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use Composer\Config;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Repository\ComposerRepository;
use Composer\Repository\VcsRepository;
use Composer\Util\HttpDownloader;

class ComposerClient
{
    public function createComposerRepository(Package|Registry $registry, ?IOInterface $io = null, ?Config $config = null): ComposerRepository
    {
        $registry = $registry instanceof Package ? $registry->getMirrorRegistry() : $registry;

        $config ??= ConfigFactory::createForRegistry($registry);
        if (!$io) {
            $io = new NullIO();
            $io->loadConfiguration($config);
        }
        $httpDownloader = $this->createHttpDownloader($io, $config);

        return new ComposerRepository(['url' => $registry->getUrl()], $io, $config, $httpDownloader);
    }

    public function createVcsRepository(Package $package, ?IOInterface $io = null, ?Config $config = null): VcsRepository
    {
        $repoUrl = $package->getRepositoryUrl();

        $config ??= ConfigFactory::createForVcsRepository($repoUrl, $package->getRepositoryCredentials());
        if (!$io) {
            $io = new NullIO();
            $io->loadConfiguration($config);
        }
        $httpDownloader = $this->createHttpDownloader($io, $config);

        return new VcsRepository(['url' => $repoUrl], $io, $config, $httpDownloader);
    }

    public function createHttpDownloader(?IOInterface $io = null, ?Config $config = null): HttpDownloader
    {
        $config ??= Factory::createConfig();
        if (!$io) {
            $io = new NullIO();
            $io->loadConfiguration($config);
        }

        return new HttpDownloader($io, $config, self::getHttpDownloaderOptions());
    }

    public static function getHttpDownloaderOptions(): array
    {
        $options['http']['header'][] = 'User-Agent: Dirigent (https://github.com/codedmonkey/dirigent)';
        $options['max_file_size'] = 128_000_000;

        return $options;
    }
}
