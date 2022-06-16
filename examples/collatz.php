<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

$startNumber = mt_rand(1, 100_000);

echo 'Produce Collatz series using recursive feed of stream with values produced by its self:', PHP_EOL;

Stream::of($startNumber)
    ->call(Consumers::stdout(', '))
    ->while(Filters::greaterThan(1))
    ->mapWhen(
        static fn(int $n): bool => ($n & 1) === 0,
        static fn(int $n): int => $n >> 1,
        static fn(int $n): int => (3 * $n + 1)
    )
    ->loop(true);

echo PHP_EOL, PHP_EOL, 'Produce Collatz series using QueueProducer:', PHP_EOL;

$queue = Producers::queue([$startNumber]);

Stream::from($queue)
    ->call(Consumers::stdout(', '))
    ->while(Filters::greaterThan(1))
    ->mapWhen(
        static fn(int $n): bool => ($n & 1) === 0,
        static fn(int $n): int => $n >> 1,
        static fn(int $n): int => (3 * $n + 1)
    )
    ->call($queue)
    ->run();

echo PHP_EOL;