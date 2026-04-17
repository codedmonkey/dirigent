<?php

namespace CodedMonkey\Dirigent\Tests\Helper;

trait KernelTestCaseTrait
{
    /**
     * @template TServiceClass of object
     *
     * @param class-string<TServiceClass> $class
     *
     * @return TServiceClass
     */
    protected function getService(string $class, ?string $name = null): object
    {
        return self::getContainer()->get($name ?: $class);
    }
}
