<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/bin');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => ['import_classes' => true],
        // PHP 7.4 : pas de virgule finale dans les listes de paramètres (PHP 8.0+).
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'match'],
        ],
    ])
    ->setFinder($finder);
