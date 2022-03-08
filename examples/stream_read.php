<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Producer\Resource\TextFileReader;
use FiiSoft\Jackdaw\Stream;

$timeStart = microtime(true);
$memoryStart = memory_get_usage();

require_once  __DIR__ .'/../vendor/autoload.php';

$counter = Consumers::counter();

$stream = Stream::from(new TextFileReader(fopen(__DIR__.'/../var/testfile.txt', 'rb')))
    ->map(Mappers::jsonDecode())
    ->filterBy('isVerified', true)
    ->filterBy('facebookId', Filters::notNull())
    ->filterBy('credits', Filters::greaterOrEqual(500000))
    ->filterBy('scoring', Filters::greaterOrEqual(95.0))
    ->filterBy('name', Filters::length()->eq(10))
    ->call($counter)
    ->extract(['id', 'credits'])
    ->sortBy('credits desc', 'id asc')
    ->limit(20);

echo 'best 20 rows: ', PHP_EOL;

foreach ($stream as $row) {
    echo 'id: ', $row['id'],' credits: ', $row['credits'], PHP_EOL;
}

echo PHP_EOL, 'total found rows: ', $counter->count(), PHP_EOL;

$memoryStop = memory_get_usage();
$timeStop = microtime(true);

echo 'memory usage: ', $memoryStop - $memoryStart, ' (peak: ', memory_get_peak_usage(true), ')', PHP_EOL,
    'execution time: ', ($timeStop - $timeStart), PHP_EOL;