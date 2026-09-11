<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;

readonly class DistributionMirrorer
{
    public function __construct(
        private DistributionFilesystem $distributionFilesystem,
        private ComposerClient $composer,
    ) {
    }

    public function mirror(Distribution $distribution, string $path): bool
    {
        $url = $distribution->getMetadata()->getDistributionUrl();

        $this->distributionFilesystem->createDirectory($path);

        $httpDownloader = $this->composer->createHttpDownloader();
        $httpDownloader->copy($url, $path);

        $distribution->setSource($url);

        return true;
    }
}
