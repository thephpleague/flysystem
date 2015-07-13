<?php

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'phpdoc_params',
        'operators_spaces',
        'ordered_use',
        'short_array_syntax',
        'return',
        'spaces_before_semicolon',
        'spaces_cast',
        'ternary_spaces',
        'eof_ending',
    ])
    ->finder(Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->in(__DIR__.'/stub'));
