<?php

namespace CodedMonkey\Conductor\Composer;

class HttpDownloaderOptionsFactory
{
    public static function getOptions(): array
    {
        $options['http']['header'][] = 'User-Agent: Conductor (https://github.com/codedmonkey/conductor)';
        $options['max_file_size'] = 128_000_000;

        return $options;
    }
}
