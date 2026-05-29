<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
class IsGrantedAccess
{
}
