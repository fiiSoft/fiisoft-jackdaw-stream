<?php declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\StreamMaker;

$buffer = new ArrayObject();

$stream = Stream::from([4, 7, 2, 'a', 8, null, 5, 3, 7])
    ->notNull()
    ->limit(6)
    ->filter('is_int')
    ->map(fn(int $x) => $x ** 2)
    ->filter(fn(int $x) => $x <= 50)
    ->collectIn($buffer)
    ->call(function ($v, $k) {
        echo 'key: ', $k;
    })
    ->call(function ($v) {
        echo ' value: ', $v, PHP_EOL;
    })
;

echo print_r($stream->toArray(), true), 'data collected in buffer: ', json_encode($buffer->getArrayCopy()), PHP_EOL;

$stream = StreamMaker::from([4, 'c' => 7, 2, 'a', 'z' => 8, null, 5, '', 3, 7]);

foreach ($stream->start()->filter('is_int')->limit(5)->skip(2) as $key => $value) {
    echo 'key: ', $key,' value: ', $value, PHP_EOL;
}

echo 'count: ', $stream->start()->notEmpty()->count()->get(), PHP_EOL;

echo 'first: ', Stream::from(['a', 5, 'b', 3, 'c'])->filter('is_int')->first()->get(), PHP_EOL;

echo 'last: ', Stream::from(['a', 5, 'b', 3, 'c'])->filter('is_int')->lastOrElse(0)->get(), PHP_EOL;

echo 'only a,b,c: ', Stream::from(['a', 5, 'b', 3, 'c'])->only(['a', 'b', 'c'])->toString(), PHP_EOL;

echo 'some random numbers: ', Producers::randomInt(10, 99)->stream()->limit(5)->toJson(), PHP_EOL;

echo 'join: ', Stream::from(['a', 'b', 'c'])->join([1, 2, 3])->skip(2)->limit(2)->toJson(), PHP_EOL;

$integersFrom1To10 = StreamMaker::from(Producers::sequentialInt(1, 1, 10));

echo 'min: ', $integersFrom1To10->start()->reduce(Reducers::min())->get(), PHP_EOL;
echo 'max: ', $integersFrom1To10->start()->reduce(Reducers::max())->get(), PHP_EOL;
echo 'sum: ', $integersFrom1To10->start()->reduce(Reducers::sum())->get(), PHP_EOL;
echo 'avg: ', $integersFrom1To10->start()->reduce(Reducers::average())->get(), PHP_EOL;

echo 'min: ', $integersFrom1To10->start()->reduce('min')->get(), PHP_EOL;
echo 'max: ', $integersFrom1To10->start()->reduce('max')->get(), PHP_EOL;

$someNumbers = StreamMaker::from([8,3,6,2,7]);

echo 'min from ', $someNumbers->start()->toString(),
    ' is ', $someNumbers->start()->reduce(Reducers::min())->get(), PHP_EOL;

echo 'max from ', $someNumbers->start()->toString(),
    ' is ', $someNumbers->start()->reduce(Reducers::max())->get(), PHP_EOL;

echo 'unique values: ', Stream::from([1,0,2,9,3,8,4,7,5,6,1,0,2,9,3,8,4,8,5,7,6])
    ->unique()->skip(5)->limit(5)->toString(), PHP_EOL;

Stream::from(['0','zzz','6','3','aaa','2','6','','1','5','0','2'])
    ->onlyNumeric()
    ->castToInt()
    ->greaterThan(0)
    ->unique()
    ->reindex()
    ->call(function (int $v, $k) {
        echo 'key: ', $k, ' value: ', $v, PHP_EOL;
    })
    ->run();

echo 'Stream::of ', Stream::of('a', 'b', 'c', 'd')->map('strtoupper')->toString(', '), PHP_EOL;

Stream::of(5, 'five', 2, 'six', 4, 'seven', 2)
    ->filter('is_string')
    ->forEach(function (string $item) {
        echo 'element: ', $item, PHP_EOL;
    });

$sorted = StreamMaker::from(static fn() => Stream::of(7,4,3,8,7,6,9,1,2,7,6)->unique()->sort());
echo 'sorted unique: ', $sorted->start()->toString(), PHP_EOL;

echo Stream::of(['a', 'b'], 6, 'z', 3, ['c', 'd'], $sorted->start())
    ->map(function ($v, $k) {
        return $v.'('.$k.')';
    })
    ->toString(' '), PHP_EOL;


echo 'sorted: ', Stream::from([6,3,8,1,9,4,0])->sort()->toJson(), PHP_EOL;

$words = StreamMaker::from(['the', 'quick', 'brown', 'fox', 'jumps', 'over', 'the', 'lazy', 'dog']);
echo 'words sorted by length asc: ', $words->start()->sort('strlen')->toString(', '), PHP_EOL;
echo 'words sorted by length desc: ', $words->start()->rsort('strlen')->toJson(), PHP_EOL;

$words = StreamMaker::from(Producers::getAdapter(['the', 'Quick', 'brown', 'Fox', 'The', 'quick', 'Brown', 'fox']));
echo 'words unsorted: ', $words->start()->toString(', '), PHP_EOL;
echo 'words sorted 1: ', $words->start()->sort()->toString(', '), PHP_EOL;
echo 'words sorted 2: ', $words->start()->sort('strtoupper')->toString(', '), PHP_EOL;
echo 'words sorted 3: ', $words->start()->sort('strtolower')->toString(', '), PHP_EOL;
echo 'words sorted 4: ', $words->start()->sort('strcmp')->toString(', '), PHP_EOL;

$numbers = StreamMaker::from([7,3,8,1,9,2,0,5,4]);
echo 'numbers: ', $numbers->start()->toJson(), PHP_EOL;
echo 'numbers in reversed order: ', $numbers->start()->reverse()->reindex()->toJson(), PHP_EOL;
echo 'numbers sorted asc: ', $numbers->start()->sort()->reindex()->toJson(), PHP_EOL;
echo 'numbers sorted desc: ', $numbers->start()->rsort()->reindex()->toJson(), PHP_EOL;
echo 'numbers in random order: ', $numbers->start()->shuffle()->reindex()->toJson(), PHP_EOL;

$words = StreamMaker::of('sometimes', 'shit', 'happens');
echo 'sort by length: ', $words->start()->sort('strlen')->toString(', '), PHP_EOL;
echo 'sort normally: ', $words->start()->sort()->toString(', '), PHP_EOL;

$assoc = StreamMaker::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
echo 'normal: ', $assoc->start()->toJson(), PHP_EOL,
    'flipped: ', $assoc->start()->flip()->toJson(), PHP_EOL,
    'map keys: ', $assoc->start()->mapKey(static fn($v, $k) => $v.'_'.$k)->toJsonAssoc(), PHP_EOL;

if ($assoc->start()->has(3)->get()) {
    echo '3 is in stream!', PHP_EOL;
}

if ($assoc->start()->hasAny([3, 5, 7])->get()) {
    echo '3 or 5 or 7 is in stream!', PHP_EOL;
}

if ($assoc->start()->hasEvery([2, 4])->get()) {
    echo 'stream contains 2 and 4!', PHP_EOL;
}

if (Stream::from([5, 3, 7, 3, 5, 1, 5, 3, 7])->hasOnly([1, 3, 5, 7])->get()) {
    echo 'stream contains only 1,3,5,7', PHP_EOL;
}

echo 'find in stream: ', print_r($assoc->start()->find('d', Check::ANY)->toArrayAssoc(), true), PHP_EOL;
echo 'find in stream: ', print_r($assoc->start()->find('d', Check::ANY)->toArray(), true), PHP_EOL;

echo 'fold: ', Stream::from([1, 1, 1])->fold(7, Reducers::sum())->get(), PHP_EOL;

echo 'example of chunking: ', Stream::from(['a','b','c','d','e','f','g','h'])
    ->chunk(3)
    ->map(Mappers::concat())
    ->toString(', '), PHP_EOL;

$numbersFrom1To5 = StreamMaker::from([1, 2, 3, 4, 5]);
echo 'example of scan for ', $numbersFrom1To5->start()->toString(),
    ' is: ', $numbersFrom1To5->start()->scan(0, Reducers::sum())->skip(1)->toString(), PHP_EOL;

echo 'another example of scan: ', StreamMaker::from(['a', 'b', 'c', 'd'])
    ->start()
    ->scan('', Reducers::concat())
    ->skip(1)
    ->toString(), PHP_EOL;

echo 'example of chunk and flat: ', PHP_EOL, Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'])
    ->chunk(3)
    ->call(function (array $vals) {
        echo 'chunked: ', implode(',', $vals), PHP_EOL;
    })
    ->flat()
    ->toString(), PHP_EOL;

$rowset = [
    ['id' => 2, 'name' => 'Kate', 'age' => 35],
    ['id' => 5, 'name' => 'Chris', 'age' => 26],
    ['id' => 8, 'name' => 'Joanna', 'age' => 18],
];

echo 'rowset 1: ', Stream::from($rowset)
    ->flat()
    ->only(['name', 'age'], Check::KEY)
    ->chunkAssoc(2)
    ->toJson()
, PHP_EOL;

echo 'rowset 2: ', Stream::from($rowset)
    ->map(Mappers::extract(['name', 'age']))
    ->toJson(), PHP_EOL;

echo 'rowset 3: ', Stream::from($rowset)
    ->flat()
    ->without(['id'], Check::KEY)
    ->chunkAssoc(2)
    ->toJson(), PHP_EOL;

$stream = Stream::from(['b','a','g','e','c'])
    ->flat()
    ->sort()
    ->reindex();

echo 'another test: ', PHP_EOL, $stream->toJson(), PHP_EOL;

echo 'sort tuples: ', Stream::from([[3, 5], [1, 4], [2]])->flat()->limit(6)->sort()->toString(), PHP_EOL;

$var1 = [['a', 'b'], ['c', 'd'], ['e', ['f', 'g', ['h', 'i']]], [[['j']]]];
$var2 = ['a', 'b', 'c', 'd', 'e', 'f'];

$substream = Stream::from($var1)->flat()->sort();
$stream = Stream::from($substream);

echo 'substream: ', $stream->limit(10)->toString(), PHP_EOL;

$stream = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'])
    ->chunk(3)
    ->map(Mappers::reverse())
    ->map(Mappers::jsonEncode());

echo 'chunked, mapped and flattened: ', $stream->toString(), PHP_EOL;

$stream = Stream::from(['["c","b","a"]','["f","e","d"]','["h","g"]'])
    ->map(Mappers::jsonDecode())
    ->flat(1);

echo 'decoded: ', $stream->toString(), PHP_EOL;

echo 'example of usin flatMap: ', Stream::from(['the quick brown fox jumps'])
    ->flatMap(Mappers::split())
    ->without(['red', 'brown', 'blue'])
    ->map('ucfirst')
    ->sort('strlen')
    ->reindex()
    ->toJson(), PHP_EOL;

echo 'iterate over stream: ';

$source = StreamMaker::from([9, 4, 2, 7, 3, 5, 9, 3, 6, 1, 2, 1, 5, 7, 3, 4, 9, 7, 8]);
foreach ($source as $item) {
    echo $item, ',';
}

echo PHP_EOL;

echo 'average value of triplets: ', $source->start()->chunk(3)->map(Reducers::average(2))->toString(', '), PHP_EOL;

$stream = StreamMaker::of('a', 'v', 3, 'z');
echo 'example of while: ', $stream->start()->while('is_string')->map('strtoupper')->toString(), PHP_EOL;
echo 'example of until: ', $stream->start()->until('is_int')->map('strtoupper')->toString(), PHP_EOL;

$streams = $stream->start()->groupBy('is_int');
echo 'only integers: ', $streams->get(true)->toString(), PHP_EOL;
echo 'only non-integers: ', $streams->get(false)->toString(), PHP_EOL;

$rowset = [
    ['id' => 2, 'name' => 'Kate', 'age' => 35],
    ['id' => 9, 'name' => 'Chris', 'age' => 26],
    ['id' => 6, 'name' => 'Joanna', 'age' => 35],
    ['id' => 5, 'name' => 'Chris', 'age' => 26],
    ['id' => 7, 'name' => 'Sue', 'age' => 17],
];

echo 'sort by fields: ', Stream::from($rowset)->sortBy('age asc', 'name desc', 'id')->toJson(), PHP_EOL;

$byName = Stream::from($rowset)->groupBy('name');
echo 'rowset groups by name for streams: ', Stream::from($byName->classifiers())->toString(), PHP_EOL;

echo 'number of rows with name Chris: ', $byName->get('Chris')->count(), PHP_EOL;

echo 'remove id from rows: ', Stream::from($rowset)->remove('id')->toJson(), PHP_EOL;
echo 'remove id and age from rows: ', Stream::from($rowset)->remove(['id', 'age'])->toJson(), PHP_EOL;

//that stuff is fuckin crazy
echo 'how to send data from one stream to another', PHP_EOL;

$target = Stream::empty()->limit(5)->chunk(2)->map('array_sum')->call(function (int $sum) {
    echo 'sum of pair: ', $sum, PHP_EOL;
});

$minValue = Stream::empty()->reduce('min');
$maxValue = Stream::empty()->reduce('max');

$lastFive = Stream::empty()
    ->tail(5)
    ->feed($minValue)
    ->feed($maxValue)
    ->call(Consumers::printer(Check::VALUE))
    ->chunk(5)
    ->call(static function (array $chunk) {
        Stream::from($chunk)->reduce('array_sum')->call(Consumers::printer(Check::VALUE));
    });

$count = Stream::empty()->count();

$numOfNumbers = $source->start()->feed($count)->feed($target)->feed($lastFive)->greaterThan(5)->count();
$numOfNumbers->run();

echo 'total num of numbers: ', $count->get(), PHP_EOL;
echo 'num of numbers greater than 5: ', $numOfNumbers->get(), PHP_EOL;
echo 'min value of last 5 elements: ', $minValue->get(), PHP_EOL;
echo 'max value of last 5 elements: ', $maxValue->get(), PHP_EOL;

//another example

$minValue = Stream::empty()->reduce('min');
$source->start()->feed($minValue)->run();

echo 'min value from feed: ', $minValue->get(), PHP_EOL;

$minValue = Stream::from([8,2,6,1,4])->reduce('min')->get();
echo 'min value: ', $minValue, PHP_EOL;

//lazy result
echo 'lazy-evaluated result: ',
    $source->start()->find(Filters::greaterThan(100))->getOrElse(static fn(): int => -1),
    PHP_EOL;

//transform result
echo 'transformed result: ',
    $source->start()->find(Filters::isInt())->transform(static fn(int $n): int => $n - 100)->get(),
    PHP_EOL;

//collect feed
$collector = Stream::empty()->onlyIntegers()->collect();
Stream::from(['a', 1, 'b', 2])->feed($collector)->onlyStrings()->run();

echo 'collected numbers: ', implode(',', $collector->get()), PHP_EOL;

echo 'sum of collected numbers: ', $collector->transform('array_sum')->get(), PHP_EOL;

//aggregate some values
echo 'aggregate example: ', Stream::from($rowset)->flat()->aggregate(['id', 'age'])->toJsonAssoc(), PHP_EOL;

//only arrays with keys
echo 'only with keys: ', Stream::from([
    ['id' => 15, 'name' => 'Agatha'],
    ['id' => 4, 'name' => null],
])->onlyWith(['name'])->toJsonAssoc(), PHP_EOL;

//call consumer only once
echo 'call once: ';
Stream::from($rowset)->extract('name')->flat()->callOnce(Consumers::printer(Check::VALUE))->run();

//or twice:
echo 'call twice: ', PHP_EOL;
Stream::from($rowset)->extract('name')->flat()->callMax(2, Consumers::printer())->run();

//example of conditional mapper
echo 'conditional map: ',
    Stream::from(['a', 1, 'b', 2, 'c', 3])->mapWhen('is_string', 'strtoupper')->toString(),
    PHP_EOL;

//example of best sorting
$rowset = [
    ['name' => 'Chris', 'score' => 26],
    ['name' => 'Joanna', 'score' => 18],
    ['name' => 'Kate', 'score' => 35],
    ['name' => 'John', 'score' => 12],
    ['name' => 'David', 'score' => 42],
];

echo 'two best players: ', PHP_EOL;
Stream::from($rowset)
    ->best(2, Comparators::sortBy(['score desc', 'name']))
    ->map(Mappers::concat(' '))
    ->forEach(Consumers::printer(Check::VALUE));

//or...
echo 'or...', PHP_EOL;
Stream::from($rowset)
    ->sortBy('score desc', 'name')
    ->limit(2)
    ->map(Mappers::concat(' '))
    ->forEach(Consumers::printer(Check::VALUE));

//examples of tokenize strings
echo 'tokenize string by direct use of tokenizer producer: ', PHP_EOL;
$tokenizer = Producers::tokenizer(' ', 'this is string that will be tokenized');
Stream::from($tokenizer)->map('strrev')->forEach(Consumers::stdout(', '));

$tokenizer->restartWith('this is another string to tokenize');
Stream::from($tokenizer)->map('ucfirst')->forEach(STDOUT);

echo PHP_EOL;

echo 'tokenize elements of stream using tokenize method: ', PHP_EOL;
Stream::from(['this is first string', 'this is second string'])
    ->tokenize()
    ->map('ucfirst')
    ->forEach(Consumers::stdout(', '));

echo PHP_EOL;

echo 'Flat nested arrays by using flattener producer directly: ', PHP_EOL;
Producers::flattener($rowset)->stream()->forEach(Consumers::stdout(', ', Check::BOTH));

echo PHP_EOL, 'and the same using flat method: ', PHP_EOL;
Stream::from($rowset)->flat()->forEach(Consumers::stdout(', ', Check::BOTH));

echo PHP_EOL, "Let's map key and value at the same time: ",
    Stream::from([['id' => 2, 'name' => 'Kate', 'age' => 35], ['id' => 9, 'name' => 'Chris', 'age' => 26]])
        ->mapKV(static fn(array $row): array => [$row['id'] => Mappers::extract('name')])
        ->toJsonAssoc();

echo PHP_EOL;

echo 'Push to non-empty stream: ';
Stream::from(['foo', 123, 'bar', 456])
    ->feed(
        Stream::from(['a', 1, 'b', 2, 'c', 3, 'd'])
            ->onlyStrings()
            ->call(Consumers::stdout(' '))
    )
    ->onlyIntegers()
    ->forEach(Consumers::stdout('-'));

echo PHP_EOL;

echo 'Left only string values in arrays: ',
    Stream::from($rowset)->extractWhen('is_string')->notEmpty()->toJson(),
    PHP_EOL;

//let's do some fun with Collatz:
echo 'let\'s play with random Collatz series: ', PHP_EOL;
Producers::collatz()->stream()->forEach(Consumers::stdout(' '));

echo PHP_EOL;

echo 'number of lines in this file with PHP_EOL: ',
    Stream::from(new \SplFileObject(__FILE__))->filter(Filters::contains('PHP_EOL'))->count()->get();

echo PHP_EOL;