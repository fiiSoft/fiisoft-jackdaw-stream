<?php

use Flow\ETL\DSL\Entry;
use Flow\ETL\DSL\Transform;
use Flow\ETL\ETL;
use Flow\ETL\Extractor;
use Flow\ETL\Row;
use Flow\ETL\Row\Sort;
use Flow\ETL\Rows;

$timeStart = microtime(true);
$memoryStart = memory_get_usage();

require_once  __DIR__ .'/../vendor/autoload.php';

$extractor = new class implements Extractor {
    public function extract(): \Generator
    {
        $fp = fopen(__DIR__.'/../var/testfile.txt', 'rb');
        
        $line = fgets($fp);
        while ($line !== false) {
            yield new Rows(Row::create(Entry::string('line', $line)));
            $line = fgets($fp);
        }
    }
};

echo 'best 20 rows: ', PHP_EOL;

$etl = ETL::read($extractor)
    ->transform(Transform::to_array_from_json('line'))
    ->transform(Transform::array_unpack('line'))
    ->filter(static fn(Row $row) => $row->valueOf('isVerified'))
    ->filter(static fn(Row $row) => $row->valueOf('facebookId') !== null)
    ->filter(static fn(Row $row) => $row->valueOf('credits') >= 500000)
    ->filter(static fn(Row $row) => $row->valueOf('scoring') >= 95.0)
    ->filter(static fn(Row $row) => mb_strlen($row->valueOf('name')) === 10)
    ->rows(Transform::keep('id', 'credits'))
    ->sortBy(Sort::desc('credits'), Sort::asc('id'))
;

$rows = $etl->fetch()->toArray();

foreach (array_slice($rows, 0, 20) as $row) {
    echo 'id: ', $row['id'],' credits: ', $row['credits'], PHP_EOL;
}

echo PHP_EOL, 'total found rows: ', \count($rows), PHP_EOL;

$memoryStop = memory_get_usage();
$timeStop = microtime(true);

echo 'memory usage: ', $memoryStop - $memoryStart, ' (peak: ', memory_get_peak_usage(true), ')', PHP_EOL,
'execution time: ', ($timeStop - $timeStart), PHP_EOL;