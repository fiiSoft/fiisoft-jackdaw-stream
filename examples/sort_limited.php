<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Mappers;
use loophp\collection\Contract\Operation\Sortable;

require_once  __DIR__ .'/../vendor/autoload.php';

$file = fopen(__DIR__.'/../var/testfile.txt', 'rb');

$reader = static function ($fp): \Generator {
    $index = 0;
    $line = fgets($fp);
    while ($line !== false) {
        yield $index++ => $line;
        $line = fgets($fp);
    }
};

$timeStart = microtime(true);
$memoryStart = memory_get_usage();

//---------------------------------------------------------------------------------------

//------ aimeos
//$best = \Aimeos\Map::from($reader($file))
//    ->map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
//    ->filter(static fn(array $row): bool => $row['isVerified'])
//    ->filter(static fn(array $row): bool => $row['credits'] >= 500000)
//    ->usort(static fn(array $a, array $b): int => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id'])
//    ->take(10_000)
//    ->toArray();

//------ bertptrs
//$best = (new \phpstreams\Stream($reader($file)))
//    ->map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
//    ->filter(static fn(array $row): bool => $row['isVerified'])
//    ->filter(static fn(array $row): bool => $row['credits'] >= 500000)
//    ->sorted(static fn(array $a, array $b): int => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id'])
//    ->limit(10_000)
//    ->toArray();

//------ ebanx
//$rows = \EBANX\Stream\Stream::of($reader($file))
//    ->map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
//    ->filter(static fn(array $row): bool => $row['isVerified'])
//    ->filter(static fn(array $row): bool => $row['credits'] >= 500000)
//    ->collect();
//
//usort($rows, static fn(array $a, array $b): int => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id']);
//$best = array_slice($rows, 0, 10_0000);

//------ gowork
//$best = \GW\Value\Wrap::iterable($reader($file))
//    ->map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
//    ->filter(static fn(array $row): bool => $row['isVerified'])
//    ->filter(static fn(array $row): bool => $row['credits'] >= 500000)
//    ->toArrayValue()
//    ->sort(static fn(array $a, array $b): int => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id'])
//    ->take(10_000)
//    ->toArray();

//------ illuminate
//$best = (new \Illuminate\Support\Collection($reader($file)))
//    ->map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
//    ->filter(static fn(array $row): bool => $row['isVerified'])
//    ->filter(static fn(array $row): bool => $row['credits'] >= 500000)
//    ->sortBy([['credits', 'desc'], ['id', 'asc']])
//    ->take(10_000)
//    ->toArray();

//------ loophp
//$best = \loophp\collection\Collection::fromGenerator($reader($file))
//    ->map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
//    ->filter(static fn(array $row): bool => $row['isVerified'])
//    ->filter(static fn(array $row): bool => $row['credits'] >= 500000)
//    ->sort(
//        Sortable::BY_VALUES,
//        static fn(array $a, array $b): int => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id']
//    )
//    ->limit(10_000)
//    ->all();

//------ sanmai
//$rows = \Pipeline\take($reader($file))
//    ->map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
//    ->filter(static fn(array $row): bool => $row['isVerified'])
//    ->filter(static fn(array $row): bool => $row['credits'] >= 500000)
//    ->toArray();
//
//usort($rows, static fn(array $a, array $b): int => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id']);
//$best = array_slice($rows, 0, 10_000);

//------ jackdaw
//$best = \FiiSoft\Jackdaw\Stream::from($file)
//    ->map(Mappers::jsonDecode())
//    ->filterBy('isVerified', true)
//    ->filterBy('credits', Filters::greaterOrEqual(500000))
//    ->sortBy('credits desc', 'id asc')
//    ->limit(10_000)
//    ->toArrayAssoc();

//------ pure PHP
$best = [];
foreach ($reader($file) as $line) {
    $entry = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    if ($entry['isVerified'] && $entry['credits'] >= 500000) {
        $best[] = $entry;
    }
}

usort($best, static fn(array $a, array $b): int => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id']);
$best = array_slice($best, 0, 10_000);

//---------------------------------------------------------------------------------------

echo 'total rows: ', \count($best), PHP_EOL;

$memoryStop = memory_get_usage();
$timeStop = microtime(true);

echo 'memory usage: ', number_format($memoryStop - $memoryStart, 0, '.', '_')
    , ' (peak: ', number_format(memory_get_peak_usage(), 0, '.', '_'), ')', PHP_EOL
    , 'execution time: ', ($timeStop - $timeStart), PHP_EOL;