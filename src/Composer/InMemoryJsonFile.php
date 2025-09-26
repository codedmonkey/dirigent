<?php

/*
 * © CleverMobi
 */

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Composer;

use Composer\Json\JsonFile;

class InMemoryJsonFile extends JsonFile
{
    public function __construct(
        private readonly string $contents,
    ) {
        parent::__construct('in-memory');
    }

    public function exists(): bool
    {
        return true;
    }

    public function read(): array
    {
        return static::parseJson($this->contents, 'in-memory');
    }

    public function write(array $hash, int $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    {
        throw new \LogicException();
    }
}
