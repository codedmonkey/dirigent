<?php

namespace CodedMonkey\Conductor\Registry;

use Symfony\Component\Filesystem\Filesystem;

class RegistryCachePool
{
    public function __construct(
        private readonly string $storagePath,
    ) {
    }

    public function exists(string $key): bool
    {
        return file_exists("{$this->storagePath}/{$key}.json");
    }

    public function read(string $key): RegistryCacheItem
    {
        $item = new RegistryCacheItem($key);

        if (!$this->exists($key)) {
            return $item;
        }

        $contents = file_get_contents("{$this->storagePath}/{$key}.json");
        $data = json_decode($contents, true);

        $storageData = $data['_metadata'];
        unset($data['_metadata']);

        $item->content = $data;
        $item->degraded = $storageData['degraded'];
        $item->found = $storageData['found'];
        $item->lastModified = $storageData['last-modified'];
        $item->lastResolved = $storageData['last-resolved'];

        return $item;
    }

    public function write(RegistryCacheItem $item): void
    {
        $key = $item->key;
        $path = "{$this->storagePath}/{$key}.json";

        (new Filesystem())->mkdir(dirname($path));

        $data = [...$item->content, '_metadata' => [
            'degraded' => $item->degraded,
            'found' => $item->found,
            'last-modified' => $item->lastModified,
            'last-resolved' => $item->lastResolved,
        ]];
        $contents = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        file_put_contents($path, $contents);
    }
}
