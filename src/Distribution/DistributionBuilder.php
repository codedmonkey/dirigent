<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use CodedMonkey\Dirigent\Composer\ConfigFactory;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Git as GitUtility;
use Composer\Util\ProcessExecutor;
use Composer\Util\Url;

readonly class DistributionBuilder
{
    public function __construct(
        private DistributionFilesystem $distributionFilesystem,
    ) {
    }

    public function build(Distribution $distribution): bool
    {
        $metadata = $distribution->getMetadata();

        $package = $metadata->getPackage();
        $repositoryUrl = $package->getRepositoryUrl();
        $distributionPath = $this->distributionFilesystem->path($metadata, $distribution->getType());

        $composerConfig = ConfigFactory::createForVcsRepository($repositoryUrl, $package->getRepositoryCredentials());
        $composerIo = new NullIO();
        $composerIo->loadConfiguration($composerConfig);

        $gitUtility = new GitUtility(
            $composerIo,
            $composerConfig,
            $process = new ProcessExecutor($composerIo),
            new ComposerFilesystem($process),
        );

        $cacheRepositoryName = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($repositoryUrl));
        $cachePath = $composerConfig->get('cache-vcs-dir') . '/' . $cacheRepositoryName . '/';

        if (!$gitUtility->fetchRefOrSyncMirror($repositoryUrl, $cachePath, $distribution->getReference())) {
            return false;
        }

        $this->distributionFilesystem->createDirectory($distributionPath);

        $gitUtility->runCommands([
            ['git', 'archive', '--format=zip', "--output=$distributionPath", $distribution->getReference()],
        ], $repositoryUrl, $cachePath);

        $distribution->setSource(null);

        return true;
    }
}
