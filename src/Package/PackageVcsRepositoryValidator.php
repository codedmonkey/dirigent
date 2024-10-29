<?php

namespace CodedMonkey\Conductor\Package;

use CodedMonkey\Conductor\Composer\ConfigFactory;
use CodedMonkey\Conductor\Composer\HttpDownloaderOptionsFactory;
use CodedMonkey\Conductor\Doctrine\Entity\Package;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Repository\Vcs\GitHubDriver;
use Composer\Repository\VcsRepository;
use Composer\Util\HttpDownloader;

readonly class PackageVcsRepositoryValidator
{
    public function validate(Package $package): array
    {
        $repoUrl = $package->getRepositoryUrl();

        // prevent local filesystem URLs
        if (Preg::isMatch('{^(\.|[a-z]:|/)}i', $repoUrl)) {
            return ['error' => 'Local filesystem repositories are not allowed'];
        }

        // avoid user@host URLs
        if (Preg::isMatch('{https?://.+@}', $repoUrl)) {
            return ['error' => 'Passing credentials in the repository URL is not allowed, create credentials first'];
        }

        // validate that this is a somewhat valid URL
        if (!Preg::isMatch('{^([a-z0-9][^@\s]+@[a-z0-9-_.]+:\S+ | [a-z0-9]+://\S+)$}Dx', $repoUrl)) {
            return ['error' => 'Invalid repository URL'];
        }

        // block env vars & ~ prefixes
        if (Preg::isMatch('{^[%$~]}', $repoUrl)) {
            return ['error' => 'Invalid repository URL'];
        }

        try {
            $io = new NullIO();
            $config = ConfigFactory::createForVcsRepository($repoUrl);

            $io->loadConfiguration($config);
            $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());
            $repository = new VcsRepository(['url' => $repoUrl], $io, $config, $httpDownloader);

            $driver = $repository->getDriver();
            if (!$driver) {
                return ['error' => 'Unable to find suitable VCS driver'];
            }
            $information = $driver->getComposerInformation($driver->getRootIdentifier());
            if (!isset($information['name']) || !is_string($information['name'])) {
                return ['error' => 'The package doesn\'t have a name'];
            }

            $result = [
                'error' => null,
                'name' => trim($information['name']),
                'remoteId' => null,
            ];

            if ($driver instanceof GitHubDriver) {
                if ($repoData = $driver->getRepoData()) {
                    $result['remoteId'] = parse_url($repoUrl, PHP_URL_HOST).'/'.$repoData['id'];
                }
            }

            return $result;
        } catch (\Exception $e) {
            return ['error' => '[' . get_class($e) . '] ' . $e->getMessage()];
        }
    }

    public function loadResult(Package $package, array $result): void
    {
        $package->setName($result['name']);
        $package->setRemoteId($result['remoteId']);
    }
}
