<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

echo 'Produce Collatz series using recursive feed of stream with values produced by its self:', PHP_EOL;

$collatz = Stream::empty()
    ->call(Consumers::stdout(', '))
    ->while(Filters::greaterThan(1))
    ->mapWhen(
        static fn(int $n): bool => ($n & 1) === 0,
        static fn(int $n): int => $n >> 1,
        static fn(int $n): int => (3 * $n + 1)
    )
    ->loop();

Stream::of(mt_rand(1, 100_000))->feed($collatz)->run();

echo PHP_EOL;