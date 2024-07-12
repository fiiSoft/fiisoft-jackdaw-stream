<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

//run this:
//> php producer.php | php consumer.php

Stream::from(STDIN)
    ->trim()
    ->castToInt()
    ->greaterThan(0)
    ->mapKey(static fn(int $n): int => ($n % 3 === 0 ? 2 : 0) | ($n % 5 === 0 ? 1 : 0))
    ->map(static fn(int $n, int $k): string => [$n, 'Buzz', 'Fizz', 'Fizz Buzz'][$k].', ')
    ->forEach(STDOUT);

echo PHP_EOL;
