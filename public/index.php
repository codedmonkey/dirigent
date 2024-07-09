<?php

use CodedMonkey\Conductor\AppKernel;

set_time_limit(300);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new AppKernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
