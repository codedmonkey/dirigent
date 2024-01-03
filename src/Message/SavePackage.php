<?php

namespace CodedMonkey\Conductor\Message;

class SavePackage
{
    public function __construct(
        public readonly string $packageName,
    ) {
    }
}
