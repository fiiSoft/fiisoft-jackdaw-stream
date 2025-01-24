<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

$startNumber = mt_rand(1, 100_000);

$prototype = Stream::empty()
    ->call(Consumers::stdout(', '))
    ->while(Filters::greaterThan(1))
    ->mapWhen(
        static fn(int $n): bool => ($n & 1) === 0,
        static fn (int $n): int => $n >> 1,
        static fn (int $n): int => (3 * $n + 1),
    );

//-----------------

echo 'Produce Collatz series using recursive feed of stream with values produced by itself:', PHP_EOL;

$prototype->wrap([$startNumber])->loop()->run();

//-----------------

echo PHP_EOL, PHP_EOL, 'Produce Collatz series using QueueProducer for storing current value:', PHP_EOL;

$queue = Producers::queue([$startNumber]);
$prototype->wrap($queue)->call($queue)->run();

//-----------------

echo PHP_EOL, PHP_EOL, 'Produce Collatz series using Registry for storing current value:', PHP_EOL;

$registry = Registry::new()->entry(Check::VALUE, $startNumber);
$prototype->wrap($registry)->remember($registry)->run();

//-----------------

echo PHP_EOL, PHP_EOL, 'Produce Collatz series using Memo for storing current value:', PHP_EOL;

$memo = Memo::value($startNumber);
$prototype->wrap($memo)->remember($memo)->run();

//-----------------

echo PHP_EOL, PHP_EOL, 'Produce Collatz series using reference to local variable for storing current value:', PHP_EOL;

$prototype->wrap(Producers::readFrom($startNumber))->putIn($startNumber)->run();

echo PHP_EOL;