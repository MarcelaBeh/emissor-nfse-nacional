<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->append([__DIR__ . '/exemples'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP83Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'declare_strict_types' => false,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'native_function_invocation' => false,
    ])
    ->setFinder($finder);
