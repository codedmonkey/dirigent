<?php

$binFinder = (new PhpCsFixer\Finder())
    ->in('bin')
    ->name('*');

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->append($binFinder);

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'linebreak_after_opening_tag' => true,
        'blank_line_between_import_groups' => false,
        'concat_space' => ['spacing' => 'one'],
        'mb_str_functions' => false,
        'native_constant_invocation' => false,
        'native_function_invocation' => false,
        'no_php4_constructor' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'php_unit_strict' => true,
        'phpdoc_order' => true,
        'strict_comparison' => true,
        'strict_param' => true,
    ]);
