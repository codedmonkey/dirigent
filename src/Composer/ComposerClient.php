<?php

namespace CodedMonkey\Dirigent\Composer;

use CodedMonkey\Dirigent\Doctrine\Entity\CredentialsType;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use Composer\Config;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Repository\ComposerRepository;
use Composer\Repository\VcsRepository;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Git as GitUtility;
use Composer\Util\HttpDownloader;
use Composer\Util\ProcessExecutor;
use Composer\Util\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

readonly class ComposerClient
{
    private Filesystem $filesystem;

    public function __construct(
        #[Autowire(param: 'dirigent.storage.path')]
        private string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
    }

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
        $repositoryUrl = $package->getRepositoryUrl();
        $repositoryCredentials = $package->getRepositoryCredentials();

        $config ??= ConfigFactory::createForVcsRepository($repositoryUrl, $repositoryCredentials);
        if (!$io) {
            $io = new NullIO();
            $io->loadConfiguration($config);
        }
        $httpDownloader = $this->createHttpDownloader($io, $config);

        $cacheRepositoryName = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($repositoryUrl));
        $cachePath = $config->get('cache-vcs-dir') . '/' . $cacheRepositoryName . '/';

        if (CredentialsType::SshKey === $repositoryCredentials->getType() && !$this->filesystem->exists($cachePath)) {
            $gitUtility = new GitUtility(
                $io,
                $config,
                $process = new ProcessExecutor($io),
                new ComposerFilesystem($process),
            );

            $keyPath = "$this->storagePath/keys/$cacheRepositoryName";

            $this->filesystem->mkdir(dirname($keyPath));
            $this->filesystem->dumpFile($keyPath, str_replace("\r", '', $repositoryCredentials->getKey() . PHP_EOL));
            $this->filesystem->chmod($keyPath, 0400);

            $gitConfig = sprintf('core.sshCommand="/usr/bin/ssh -i %s"', $keyPath);
            $gitUtility->runCommands([
                ['git', 'clone', '-c', $gitConfig, '--mirror', '%url%', $cachePath],
            ], $repositoryUrl, $cachePath, true, $lol);

            dd($lol);
        }

        return new VcsRepository(['url' => $repositoryUrl], $io, $config, $httpDownloader);
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
