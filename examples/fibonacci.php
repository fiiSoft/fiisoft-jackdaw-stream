<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

echo 'Produce first 93 elements of Fibonacci sequence using recursive stream:', PHP_EOL;

Stream::of(1)
    ->reindex(1)
    ->scan(0, Reducers::sum())
    ->call(Consumers::stdout(', '))
    ->until(93, Check::KEY)
    ->loop(true);

echo PHP_EOL;