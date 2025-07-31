<?php

namespace CodedMonkey\Dirigent\Attribute;

use CodedMonkey\Dirigent\Routing\PackageValueResolver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class MapPackage extends MapEntity
{
    public const string PACKAGE_REGEX = '[a-z0-9_.-]+/[a-z0-9_.-]+';
    public const string PACKAGE_DEV_REGEX = '[a-z0-9_.-]+/[a-z0-9_.-]+(~dev)?';

    public function __construct()
    {
        parent::__construct(
            resolver: PackageValueResolver::class,
        );
    }
}
