<?php

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Controller\Dashboard;

use CodedMonkey\Dirigent\Tests\FunctionalTests\PublicKernel;

class DashboardRootControllerPublicTest extends DashboardRootControllerTest
{
    protected static function getKernelClass(): string
    {
        return PublicKernel::class;
    }
}
