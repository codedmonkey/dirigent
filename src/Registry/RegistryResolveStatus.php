<?php

namespace CodedMonkey\Conductor\Registry;

enum RegistryResolveStatus
{
    case Degraded;
    case Fresh;
    case Modified;
    case NotFound;
}
