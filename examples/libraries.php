<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Stream as Jackdaw;
use phpstreams\Stream as Bertptrs;
use GW\Value\Wrap as Gowork;
use Illuminate\Support\LazyCollection as Illuminate;
use loophp\collection\Collection as Loophp;
use loophp\collection\Contract\Operation\Sortable;

require_once  __DIR__ .'/../vendor/autoload.php';

//A few examples of how to use different libraries to perform the same operations.

$countIterations = null;
$producer = static function () use(&$countIterations) {
    $num = false;
    $index = 0;
    $countIterations = 0;
    
    while (true) {
        ++$countIterations;
        yield $index++ => $num ? mt_rand(1, 100) : 'a';
        $num = !$num;
    }
};

//--------- Jackdaw -----------

$numbers = Jackdaw::from($producer)
    ->onlyIntegers()
    ->unique()
    ->limit(20)
    ->rsort()
    ->toString();

echo 'iterations: ', $countIterations, ', numbers: ', $numbers, PHP_EOL;

//--------- Bertptrs -----------

$numbers = (new Bertptrs($producer()))
    ->filter('is_int')
    ->distinct()
    ->limit(20)
    ->sorted(static fn(int $a, int $b): int => $b <=> $a)
    ->toArray();

echo 'iterations: ', $countIterations, ', numbers: ', implode(',', $numbers), PHP_EOL;

//--------- Gowork -----------

$numbers = Gowork::iterable($producer())
    ->filter('is_int')
    ->unique()
    ->take(20)
    ->toArrayValue()
    ->sort(static fn(int $a, int $b): int => $b <=> $a)
    ->implode(',')
    ->toString();

echo 'iterations: ', $countIterations, ', numbers: ', $numbers, PHP_EOL;

//--------- Illuminate -----------

$numbers = (new Illuminate($producer))
    ->filter(static fn($v): bool => is_int($v))
    ->unique()
    ->take(20)
    ->sortDesc()
    ->implode(',');

echo 'iterations: ', $countIterations, ', numbers: ', $numbers, PHP_EOL;

//--------- Loophp -----------

$numbers = Loophp::fromCallable($producer)
    ->filter(static fn($v): bool => is_int($v))
    ->distinct()
    ->limit(20)
    ->sort(Sortable::BY_VALUES, static fn(int $a, int $b): int => $b <=> $a)
    ->all();

echo 'iterations: ', $countIterations, ', numbers: ', implode(',', $numbers), PHP_EOL;


//-------------------------------------------------------------------------------------
//-- Sanmai and Ebanx - unable to use them because they do not provide a distinct/unique operation