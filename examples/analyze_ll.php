<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

//run:
//> ls -l | php analyze_ll.php

echo 'Example of read from STDIN and analyze result of ls -l piped to this file...', PHP_EOL;

Stream::from(STDIN)
    ->skip(1)
    ->omit(static fn(string $line): bool => strncmp($line, 'd', 1) === 0)
    ->split()
    ->map(static fn(array $line): string => $line[array_key_last($line)])
    ->sort('strlen')
    ->reverse()
    ->forEach(STDOUT);

echo PHP_EOL;