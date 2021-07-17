<?php

use GW\Value\Wrap;

$timeStart = microtime(true);
$memoryStart = memory_get_usage();

require_once  __DIR__ .'/../vendor/autoload.php';

echo 'best 20 rows: ', PHP_EOL;

$reader = new class (fopen(__DIR__.'/../var/testfile.txt', 'rb')) implements \Iterator {
    private $fp;
    private $line = null;
    private int $index = 0;
    
    public function __construct($fp) {
        $this->fp = $fp;
    }
    
    public function current() {
        return $this->line;
    }
    
    public function next(): void {
        $this->line = fgets($this->fp);
        if ($this->line === false) {
            $this->line = null;
        } else {
            ++$this->index;
        }
    }
    
    public function key(): int {
        return $this->index;
    }
    
    public function valid(): bool {
        return $this->line !== null;
    }
    
    public function rewind(): void {
        $this->line = fgets($this->fp);
    }
};

$count = 0;
$counter = static function () use (&$count) {
    ++$count;
};

$stream = Wrap::iterable($reader)
    ->map(static fn(string $line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
    ->filter(static fn(array $row) => $row['isVerified'])
    ->filter(static fn(array $row) => isset($row['facebookId']))
    ->filter(static fn(array $row) => $row['credits'] >= 500000)
    ->filter(static fn(array $row) => $row['scoring'] >= 95.0)
    ->filter(static fn(array $row) => mb_strlen($row['name']) === 10)
    ->map(static fn(array $row) => ['id' => $row['id'], 'credits' => $row['credits']])
    ->toArrayValue()
    ->each($counter)
    ->sort(static fn(array $a, array $b) => $b['credits'] <=> $a['credits'] ?: $a['id'] <=> $b['id'])
    ->slice(0, 20);

foreach ($stream as $row) {
    echo 'id: ', $row['id'],' credits: ', $row['credits'], PHP_EOL;
}

echo PHP_EOL, 'total found rows: ', $count, PHP_EOL;

$memoryStop = memory_get_usage();
$timeStop = microtime(true);

echo 'memory usage: ', $memoryStop - $memoryStart, ' (peak: ', memory_get_peak_usage(true), ')', PHP_EOL,
'execution time: ', ($timeStop - $timeStart), PHP_EOL;