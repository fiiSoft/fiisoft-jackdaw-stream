<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

echo 'The only elegant solution to the FizzBuzz problem is...', PHP_EOL;

Stream::from(Producers::sequentialInt(1, 1, 30))
    ->mapKey(static fn(int $n): int => ($n % 3 === 0 ? 2 : 0) | ($n % 5 === 0 ? 1 : 0))
    ->map(static fn(int $n, int $k): string => [$n, 'Buzz', 'Fizz', 'Fizz Buzz'][$k].', ')
    ->forEach(STDOUT);

echo PHP_EOL;