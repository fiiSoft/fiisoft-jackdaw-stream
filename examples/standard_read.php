<?php

$timeStart = microtime(true);
$memoryStart = memory_get_usage();

require_once  __DIR__ .'/../vendor/autoload.php';

echo 'best 20 rows: ', PHP_EOL;

$counter = 0;
$stream = [];

$fp = fopen(__DIR__.'/../var/testfile.txt', 'rb');

while (($line = fgets($fp)) !== false) {
    $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    
    if ($row['isVerified']
        && isset($row['facebookId'])
        && $row['credits'] >= 500000
        && $row['scoring'] >= 95.0
        && mb_strlen($row['name']) === 10
    ) {
        ++$counter;
        $stream[] = ['id' => $row['id'], 'credits' => $row['credits']];
    }
}

usort($stream, static function (array $a, array $b) {
    return $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id'];
});

$stream = array_slice($stream, 0, 20);

foreach ($stream as $row) {
    echo 'id: ', $row['id'],' credits: ', $row['credits'], PHP_EOL;
}

echo PHP_EOL, 'total found rows: ', $counter, PHP_EOL;

$memoryStop = memory_get_usage();
$timeStop = microtime(true);

echo 'memory usage: ', $memoryStop - $memoryStart, ' (peak: ', memory_get_peak_usage(true), ')', PHP_EOL,
'execution time: ', ($timeStop - $timeStart), PHP_EOL;