<?php

namespace CodedMonkey\Conductor\Composer;

class HttpDownloaderOptionsFactory
{
    public static function getOptions(): array
    {
        $options['http']['header'][] = 'User-Agent: Conductor';
        $options['max_file_size'] = 128_000_000;

        return $options;
    }
}
