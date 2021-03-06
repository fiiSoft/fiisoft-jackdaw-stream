<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

//run this:
//> php producer.php | php consumer.php

Stream::of(mt_rand(1, 1_000_000))
    ->call(Consumers::stdout())
    ->while(Filters::greaterThan(1))
    ->mapWhen(
        static fn(int $n): bool => ($n & 1) === 0,
        static fn(int $n): int => $n >> 1,
        static fn(int $n): int => (3 * $n + 1)
    )
    ->call(Consumers::usleep(100000))
    ->loop(true);
