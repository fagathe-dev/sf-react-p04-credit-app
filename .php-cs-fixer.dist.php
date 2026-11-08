<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/packages/credit-core/src',
        __DIR__ . '/packages/credit-core/tests',
        __DIR__ . '/apps/credit_api/src',
        __DIR__ . '/apps/credit_api/migrations',
        __DIR__ . '/apps/credit_front/src',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'concat_space' => ['spacing' => 'one'],
        'yoda_style' => true,
    ])
    ->setFinder($finder);
