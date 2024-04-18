<?php

namespace CodedMonkey\Conductor\Package;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class PackageProviderPool
{
    private readonly string $storagePath;

    public function __construct(
        #[Autowire(param: 'conductor.storage.path')]
        string $storagePath,
    ) {
        $this->storagePath = "$storagePath/provider";
    }

    public function exists(string $key): bool
    {
        return file_exists($this->path($key));
    }

    public function path(string $key): string
    {
        return "{$this->storagePath}/{$key}.json";
    }

    public function write(string $key, array $data): void
    {
        $path = $this->path($key);

        (new Filesystem())->mkdir(dirname($path));

        $contents = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        file_put_contents($path, $contents);
    }
}
