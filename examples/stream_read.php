<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

$timeStart = microtime(true);
$memoryStart = memory_get_usage();

$count = 0;

$stream = Stream::from(fopen(__DIR__.'/../var/testfile.txt', 'rb'))
    ->map(Mappers::jsonDecode())
    ->filterBy('isVerified', true)
    ->filterBy('facebookId', Filters::notNull())
    ->filterBy('credits', Filters::greaterOrEqual(500000))
    ->filterBy('scoring', Filters::greaterOrEqual(95.0))
    ->filterBy('name', Filters::length()->eq(10))
    ->extract(['id', 'credits'])
    ->countIn($count)
    ->sortBy('credits desc', 'id asc')
    ->limit(20);

echo 'best 20 rows: ', PHP_EOL;

foreach ($stream as $row) {
    echo 'id: ', $row['id'],' credits: ', $row['credits'], PHP_EOL;
}

echo PHP_EOL, 'total found rows: ', $count, PHP_EOL;

$memoryStop = memory_get_usage();
$timeStop = microtime(true);

echo 'memory usage: ', $memoryStop - $memoryStart, ' (peak: ', memory_get_peak_usage(true), ')', PHP_EOL,
    'execution time: ', ($timeStop - $timeStart), PHP_EOL;