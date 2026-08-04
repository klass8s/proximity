<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->append([__FILE__]);

$config = new PhpCsFixer\Config();

return $config
    ->setRules(
        [
            '@PHP80Migration' => true,
            '@PHP80Migration:risky' => true,
            '@PHP81Migration' => true,
            '@PHP82Migration' => true,
            '@PHP83Migration' => true,
            '@PHP84Migration' => true,
            '@PHP85Migration' => true,
            'protected_to_private' => false,
        ]
    )
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
