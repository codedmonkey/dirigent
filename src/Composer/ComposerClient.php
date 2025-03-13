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
use Composer\Util\HttpDownloader;
use Composer\Util\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

// use Composer\Util\Filesystem as ComposerFilesystem;
// use Composer\Util\Git as GitUtility;
// use Composer\Util\ProcessExecutor;
// use Symfony\Component\Process\Process;

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

        if (CredentialsType::SshKey === $repositoryCredentials?->getType() && !$this->filesystem->exists($cachePath)) {
            $this->cloneAuthenticatedVcsRepository($package, $cachePath);
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

    private function cloneAuthenticatedVcsRepository(Package $package, string $cachePath): void
    {
        $repositoryUrl = $package->getRepositoryUrl();
        $repositoryCredentials = $package->getRepositoryCredentials();

        $cacheRepositoryName = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($repositoryUrl));
        $keyPath = "$this->storagePath/keys/$cacheRepositoryName";

        // todo delete key file after every use
        $this->filesystem->mkdir(dirname($keyPath));
        $this->filesystem->dumpFile($keyPath, str_replace("\r", '', $repositoryCredentials->getKey() . PHP_EOL));
        $this->filesystem->chmod($keyPath, 0400);

        $nullStream = fopen('/dev/null', 'c');
        $descriptorSpec = [
            ['pipe', 'r'],
            $nullStream,
            $nullStream,
        ];

        $gitConfig = sprintf('core.sshcommand="ssh -i %s"', $keyPath);
        // todo improve error handling
        proc_open("git clone -c $gitConfig --mirror -- $repositoryUrl $cachePath", $descriptorSpec, $pipes);

        /*
        // Concepts to clone with libraries

        $gitUtility = new GitUtility(
            $io,
            $config,
            $process = new ProcessExecutor($io),
            new ComposerFilesystem($process),
        );

        // The following commands give the following output:
        //
        // ssh -i <key-file>: No such file or directory
        // fatal: Could not read from remote repository.
        //
        // This is caused by Symfony Process enclosing each argument with ' which messes with
        // git using the proper ssh command (I guess).

        $gitUtility->runCommands([
            ['git', 'clone', '-c', $gitConfig, '--mirror', '--', '%url%', $cachePath],
        ], $repositoryUrl, $cachePath, true, $lol);

        $process = new Process(['git', 'clone', '-c', $gitConfig, '--mirror', $repositoryUrl, $cachePath]);
        $process->mustRun();

        // The following code is an attempt to clone by initializing an empty
        // repository and adding the ssh command and remote afterward. Unfortunately
        // this doesn't mirror all branches like `git clone --mirror` does, which
        // Composer doesn't handle correctly.

        $mirrorCachePath = $config->get('cache-vcs-dir') . '/' . $cacheRepositoryName . '~mirror/';
        $this->filesystem->mkdir($mirrorCachePath);

        $gitUtility->runCommands([
            ['git', 'init'],
            ['git', 'config', 'core.sshCommand', "ssh -i $keyPath"],
            ['git', 'remote', 'add', 'origin', '--', '%url%'],
            ['git', 'remote', 'update', '--prune', 'origin'],
            ['git', 'remote', 'set-url', 'origin', '--', '%sanitizedUrl%'],
            ['git', 'gc', '--auto'],
        ], $repositoryUrl, $mirrorCachePath);

        $this->filesystem->rename("$mirrorCachePath/.git", $cachePath);

        $gitUtility->runCommands([
            ['git', 'config', 'core.bare', 'true'],
        ], $repositoryUrl, $cachePath);

        $this->filesystem->remove($mirrorCachePath);
        */
    }
}
