<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/migrations'])
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        'global_namespace_import' => ['import_classes' => false, 'import_functions' => false],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_align' => ['align' => 'left'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        // Test methods are named it_does_something on purpose; the fixer would
        // otherwise rewrite them into camelCase and break the convention.
        'php_unit_method_casing' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile('var/.php-cs-fixer.cache');
