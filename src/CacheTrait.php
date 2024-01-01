<?php

namespace CodedMonkey\Conductor;

use Symfony\Component\Filesystem\Filesystem;

trait CacheTrait
{
    protected function readFromCache(string $path): ?array
    {
        $cachePath = "{$this->cachePath}/{$path}";

        if (!file_exists($cachePath)) {
            return null;
        }

        $contents = file_get_contents($cachePath);
        return json_decode($contents, true);
    }

    protected function writeToCache(string $path, array $data, array $metadata): void
    {
        $cachePath = "{$this->cachePath}/{$path}";

        (new Filesystem())->mkdir(dirname($cachePath));

        $cacheData = [...$data, '_metadata' => $metadata];
        $contents = json_encode($cacheData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        file_put_contents($cachePath, $contents);
    }
}
