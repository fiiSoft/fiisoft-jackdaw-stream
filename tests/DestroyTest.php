<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate\Bucket;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\SingleItemBuffer;
use FiiSoft\Jackdaw\Producer\Internal\BucketListIterator;
use FiiSoft\Jackdaw\Producer\Internal\CircularBufferIterator;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\PushProducer;
use FiiSoft\Jackdaw\Producer\Internal\ReverseArrayIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseNumericalArrayIterator;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class DestroyTest extends TestCase
{
    public function test_Stream_destroy(): void
    {
        $buffer = [];
        $charsAndNums = Collectors::default();
        
        $onlyChars = Stream::empty()->onlyStrings()->reduce(Reducers::concat());
        $onlyNumbers = Stream::empty()->onlyIntegers()->reduce(Reducers::sum());
        
        $reversedChars = Stream::empty()->reverse()->reduce(Reducers::concat('|'));
        $multiplyNumbers = Reducers::getAdapter(static fn(int $acc, int $v): int => $acc * $v);
        
        $stream = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e'])
            ->feed($onlyChars, $onlyNumbers)
            ->mapWhen('\is_string', '\strtoupper')
            ->collectIn($charsAndNums)
            ->dispatch(
                Discriminators::yesNo('is_string', 'str', 'int'),
                [
                    'str' => $reversedChars,
                    'int' =>  $multiplyNumbers,
                ]
            )
            ->castToString()
            ->storeIn($buffer)
            ->chunk(2)
            ->filter(Filters::size()->eq(2))
            ->concat('');
        
        $collector = $stream->collect();
        
        self::assertSame(['A1', 'B2', 'C3', 'D4'], $collector->toArray());
        self::assertSame('abcde', $onlyChars->get());
        self::assertSame(10, $onlyNumbers->get());
        self::assertSame('E|D|C|B|A', $reversedChars->get());
        self::assertSame(24, $multiplyNumbers->result());
        self::assertSame(['A', '1', 'B', '2', 'C', '3', 'D', '4', 'E'], $buffer);
        self::assertSame(['A', 1, 'B', 2, 'C', 3, 'D', 4, 'E'], $charsAndNums->toArray());
        
        $collector->destroy();
        $onlyChars->destroy();
        $onlyNumbers->destroy();
        $reversedChars->destroy();
        $stream->destroy();
    }
    
    public function test_LastOperation_destroy(): void
    {
        $onlyChars = Stream::empty()->onlyStrings()->reduce(Reducers::concat());
        
        $stream = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e'])
            ->feed($onlyChars)
            ->mapWhen('\is_string', '\strtoupper');
        
        $stream->run();
        
        self::assertSame('abcde', $onlyChars->get());
        
        $onlyChars->destroy();
    }
    
    public function test_destroy_stream_with_stacked_operations(): void
    {
        $stream = Stream::from(['a b', 'c d'])
            ->tokenize()
            ->call($counter = Consumers::counter())
            ->gatherUntil(static fn(): bool => $counter->count() > 3, true);
        
        $result = $stream->toArray();
        
        self::assertSame([['a', 'b', 'c']], $result);
        self::assertSame(4, $counter->count());
        
        $stream->destroy();
    }
    
    public function test_destroy_stream_with_stacked_operations_2(): void
    {
        $count = 0;
        
        $stream = Stream::from(['a b', 'c d'])
            ->onError(OnError::abort())
            ->tokenize()
            ->call(static function () use (&$count) {
                if (++$count === 4) {
                    throw new \RuntimeException('Force break');
                }
            })
            ->collect();
        
        self::assertSame(['a', 'b', 'c'], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_ResultItem_destroy_SimpleFinalOperation(): void
    {
        $collect = Stream::from(['a', 'b'])->collect();
        
        self::assertSame(['a', 'b'], $collect->get());
        self::assertSame('a', $collect->stream()->first()->get());
        
        $collect->destroy();
        
        self::assertEmpty($collect->get());
    }
    
    public function test_ResultItem_destroy_ReduceFinalOperation(): void
    {
        $collect = Stream::from(['a', 'b'])->reduce(Reducers::concat());
        
        self::assertSame('ab', $collect->get());
        self::assertSame('ab', $collect->stream()->first()->get());
        
        $collect->destroy();
        
        self::assertEmpty($collect->get());
    }
    
    /**
     * @dataProvider getDataForTestGeneralProducerDestroy
     */
    public function test_general_producer_destroy(Producer $producer): void
    {
        //when
        $producer->destroy();

        //then
        self::assertSame(0, \iterator_count($producer));
    }
    
    public static function getDataForTestGeneralProducerDestroy(): array
    {
        return [
            'QueueProducer' => [Producers::queue(['a', 'b', 'c'])],
            'MultiProducer' => [Producers::multiSourced(Producers::queue(['a', 'b']), Producers::getAdapter(['c']))],
            'BucketListIterator' => [new BucketListIterator([new Bucket(), new Bucket(), new Bucket()])],
            'CircularBufferIterator' => [new CircularBufferIterator(self::convertToItems(['a', 'b', 'c']), 3, 1)],
            'ForwardItemsIterator' => [new ForwardItemsIterator(self::convertToItems([1, 2, 3]))],
            'ReverseArrayIterator' => [new ReverseArrayIterator(['a', 'b', 'c'])],
            'ReverseItemsIterator' => [new ReverseItemsIterator(self::convertToItems(['a', 'b', 'c']))],
            'ReverseNumericalArrayIterator' => [new ReverseNumericalArrayIterator(['a', 'b', 'c'])],
            'ArrayIteratorAdapter' => [Producers::getAdapter(new \ArrayIterator(['a', 'b', 'c']))],
            'TraversableAdapter' => [Producers::getAdapter(new \ArrayObject(['a', 'b', 'c']))],
        ];
    }
    
    public function test_PushProducer_destroy(): void
    {
        //given
        $otherProducer = Producers::getAdapter(['a', 'b']);
        $producer = new PushProducer($otherProducer);
        
        //when
        $producer->destroy();
        
        //then
        self::assertSame(0, \iterator_count($producer));
        self::assertSame(0, \iterator_count($otherProducer));
    }
    
    public function test_TextFileReader_destroy(): void
    {
        //given
        $fp = \fopen(__FILE__, 'rb');
        \fseek($fp, 50);
        
        $producer = Producers::getAdapter($fp);
        $numOfLinesInFile = \count(\file(__FILE__)) - 50;
        
        $countLines = $producer->stream()->count()->get();
        self::assertSame($numOfLinesInFile, $countLines - 48);
        self::assertSame($numOfLinesInFile, $countLines - 48);
        
        self::assertLessThan($numOfLinesInFile, $producer->stream()->trim()->notEmpty()->count()->get());
        
        //when
        $producer->destroy();
        
        //then
        self::assertSame(0, $producer->stream()->count()->get());
    }
    
    public function test_TextFileReader_destroy_can_close_resource(): void
    {
        //given
        $fp = \fopen(__FILE__, 'rb');
        
        $isOpen = \is_resource($fp);
        self::assertNotFalse($isOpen);
        
        $producer = Producers::resource($fp, true);
        
        //when
        $producer->destroy();
        
        //then
        $isOpen = \is_resource($fp);
        self::assertFalse($isOpen);
        
        self::assertSame(0, $producer->stream()->count()->get());
    }
    
    public function test_BucketListIterator_destroy_also_clears_buckets(): void
    {
        //given
        $bucket1 = new Bucket();
        $bucket1->data = ['a', 'b'];
        
        $bucket2 = new Bucket();
        $bucket2->data = ['c', 'd'];
        
        $producer = new BucketListIterator([$bucket1, $bucket2]);
        
        //when
        $producer->destroy();
        
        //then
        self::assertEmpty($bucket1->data);
        self::assertEmpty($bucket2->data);
    }
    
    public function test_Tokenizer_destroy(): void
    {
        //given
        $producer = Producers::tokenizer(' ', 'this is the way');
        
        self::assertSame('thisistheway', $producer->stream()->toString(''));
        self::assertSame('thisistheway', $producer->stream()->toString(''));
        
        //when
        $producer->destroy();
        
        //then
        self::assertSame('', $producer->stream()->toString());
    }
    
    public function test_Flattener_destroy(): void
    {
        //given
        $producer = Producers::flattener([['this', 'is'], ['the', ['way']]]);
        
        self::assertSame('thisistheway', $producer->stream()->toString(''));
        self::assertSame('thisistheway', $producer->stream()->toString(''));
        
        //when
        $producer->destroy();
        
        //then
        self::assertSame('', $producer->stream()->toString());
    }
    
    public function test_ResultAdapter_destroy(): void
    {
        //given
        $producer = Producers::getAdapter(Stream::from(['a', 'b', 'c'])->collect());
        
        self::assertSame(3, $producer->stream()->count()->get());
        self::assertSame(3, $producer->stream()->count()->get());
        
        //when
        $producer->destroy();
        
        //then
        self::assertSame(0, $producer->stream()->count()->get());
    }
    
    public function test_Accumulate_destroy(): void
    {
        $stream = Stream::from(['a', 1, 2, 'b', 3, 'c', 4])
            ->accumulate('is_int', true);
        
        self::assertSame([
            [1, 2],
            [3],
            [4],
        ], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_Aggregate_destroy(): void
    {
        $stream = Stream::from(['a', 1, 2, 'b', 3, 'c', '4'])
            ->mapKey(Discriminators::alternately(['foo', 'zoo', 'bar']))
            ->aggregate(['foo', 'bar']);
        
        self::assertSame([
            ['foo' => 'a', 'bar' => 2],
            ['foo' => 'b', 'bar' => 'c'],
        ], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_ChunkBy_destroy(): void
    {
        $stream = Stream::from(['a', 1, 2, 'b', 3, 'c', '4'])
            ->chunkBy('is_string', true);
        
        self::assertSame([
            ['a'],
            [1, 2],
            ['b'],
            [3],
            ['c', '4'],
        ], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_Collect_destroy(): void
    {
        $data = ['a', 1, 2, 'b', 3, 'c', 4];
        
        $stream = Stream::from(\array_flip($data))->flip()->collect();
        self::assertSame($data, $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_CollectKeys_destroy(): void
    {
        $data = ['a', 1, 2, 'b', 3, 'c', '4'];
        
        $stream = Stream::from($data)->flip()->collectKeys();
        self::assertSame($data, $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_CollectKeysIn_destroy(): void
    {
        $data = ['a', 1, 2, 'b', 3, 'c', '4'];
        $collector = Collectors::default();
        
        $stream = Stream::from($data)->flip()->collectKeysIn($collector);
        $stream->run();
        
        self::assertSame($data, $collector->toArray());
        
        $stream->destroy();
    }
    
    public function test_FilterMany_destroy(): void
    {
        $stream = Stream::from(['a', 1, 2, 'b', 3, 'c', '4'])
            ->onlyIntegers()
            ->lessThan(3);
        
        self::assertSame([1, 2], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_FilterByMany_destroy(): void
    {
        $rowset = [
            ['id' => 1, 'age' => 43, 'sex' => 'f'],
            ['id' => 4, 'age' => 62, 'sex' => 'm'],
            ['id' => 7, 'age' => 55, 'sex' => 'f'],
        ];
        
        $stream = Stream::from($rowset)
            ->filterBy('age', Filters::greaterOrEqual(50))
            ->filterBy('sex', 'f');
        
        self::assertSame([
            ['id' => 7, 'age' => 55, 'sex' => 'f'],
        ], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_Flat_destroy(): void
    {
        $stream = Stream::from(['a', ['b', ['c']]])->flat();
        
        self::assertSame(['a', 'b', 'c'], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_Fork_destroy_with_forced_break(): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd', 'e'])
            ->onError(OnError::abort())
            ->callWhen(Filters::equal('e'), static function () {
                throw new \RuntimeException('force break');
            })
            ->fork(
                Discriminators::alternately(['odd', 'even']),
                Stream::empty()->reduce(Reducers::concat())
            );
        
        self::assertEmpty($stream->toArrayAssoc());
        
        $stream->destroy();
    }
    
    public function test_GroupBy_destroy(): void
    {
        $stream = Stream::from(['a', 'b', 'a', 'b', 'c'])->flip()->group();
        
        self::assertSame([
            'a' => [0, 2],
            'b' => [1, 3],
            'c' => [4],
        ], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_HasEvery_destroy(): void
    {
        $stream = Stream::from(['c' => 'a', 'd' => 'b', 'b' => 'c', 'a' => 'd'])->hasEvery(['a', 'd'], Check::BOTH);
        
        self::assertTrue($stream->get());
        
        $stream->destroy();
    }
    
    public function test_HasOnly_destroy(): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd'])->hasOnly(['a', 'd']);
        
        self::assertFalse($stream->get());
        
        $stream->destroy();
        
    }
    
    public function test_SortLimited_destroy_with_forced_break(): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd', 'e'])
            ->onError(OnError::abort())
            ->callWhen(Filters::equal('e'), static function () {
                throw new \RuntimeException('force break');
            })
            ->best(3);
        
        self::assertEmpty($stream->toArrayAssoc());
        
        $stream->destroy();
    }
    
    public function test_MapMany_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])
            ->castToString()
            ->castToInt();
        
        self::assertSame([1, 2, 1, 3], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_Segregate_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])->segregate();
        
        self::assertSame([
            [0 => 1, 2 => 1],
            [1 => 2],
            [3 => 3],
        ], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_Shuffle_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])->shuffle();
        
        self::assertCount(4, $stream->collect()->get());
        
        $stream->destroy();
    }
    
    public function test_Sort_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])->sort();
        self::assertSame([1, 1, 2, 3], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_SortLimited_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])->best(2);
        self::assertSame([1, 1], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_SortSingle_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])->best(1);
        self::assertSame([1], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_Tail_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])->tail(2);
        self::assertSame([1, 3], $stream->toArray());
        
        $stream->destroy();
    }
    
    /**
     * @dataProvider getDataForTestUniqueDestroy
     */
    public function test_Unique_destroy($comparator, int $mode, array $expected): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   2,    'a'];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, true, 'c'];
        
        $comparison = Comparison::create($mode, $comparator);
        
        //when
        $stream = Stream::from(Producers::combinedFrom($keys, $values))->unique($comparison);
        
        //then
        self::assertSame($expected, $stream->makeTuple()->toArray());
        
        $stream->destroy();
    }
    
    public static function getDataForTestUniqueDestroy(): array
    {
        $twoArgs = static fn($a, $b): int => \gettype($a) <=> \gettype($b) ?: $a <=> $b;
        
        $fourArgs = static function ($v1, $v2, $k1, $k2): int {
            $cmp = \gettype($v1) <=> \gettype($v2) ?: $v1 <=> $v2;
            if ($cmp !== 0) {
                $cmp = \gettype($k1) <=> \gettype($k2) ?: $k1 <=> $k2;
                if ($cmp !== 0) {
                    $cmp = \gettype($v1) <=> \gettype($k2) ?: $v1 <=> $k2;
                    if ($cmp !== 0) {
                        return \gettype($v2) <=> \gettype($k1) ?: $v2 <=> $k1;
                    }
                }
            }
            
            return 0;
        };
        
        //random mode because its doesn't matter when four-argument callable is used to compare items
        $randomMode = \mt_rand(Check::VALUE, Check::ANY);
        
        return [
            //comparator, mode, expected (tuples)
            0 => [null, Check::VALUE, [[0, 'a'], ['b', 3], [2, 2], ['a', 'b'], ['b', true], ['a', 'c']]],
            [null, Check::KEY,   [[0, 'a'], ['b', 3], [2, 2], [1, 'a'],   ['a', 'b']]],
            [null, Check::ANY,   [[0, 'a'], ['b', 3], [2, 2], [1, 'a'],   ['a', 'b'], ['b', true], ['a', 'c']]],
            [null, Check::BOTH,  [[0, 'a'], ['b', 3], [2, 2], ['a', 'b']]],

            4 => [$twoArgs, Check::VALUE, [[0, 'a'], ['b', 3], [2, 2], ['a', 'b'], ['b', true], ['a', 'c']]],
            [$twoArgs, Check::KEY,   [[0, 'a'], ['b', 3], [2, 2], [1, 'a'],   ['a', 'b']]],
            [$twoArgs, Check::ANY,   [[0, 'a'], ['b', 3], [2, 2], [1, 'a'],   ['a', 'b'], ['b', true], ['a', 'c']]],
            [$twoArgs, Check::BOTH,  [[0, 'a'], ['b', 3], [2, 2], ['a', 'b']]],
            
            8 => [$fourArgs, $randomMode, [[0, 'a'], ['b', 3], [2, 2]]],
        ];
    }
    
    public function test_Uptrends_destroy(): void
    {
        $stream = Stream::from([1, 2, 1, 3])->accumulateUptrends(true);
        
        self::assertSame([[1, 2], [1, 3]], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_StreamCollection_destroy(): void
    {
        $collection = Stream::from(['a', 1, 'b', 2])->groupBy(Discriminators::yesNo('is_string', 'str', 'int'));
        
        self::assertSame(['a', 'b'], $collection->get('str')->toArray());
        self::assertSame([1, 2], $collection->get('int')->toArray());
        
        $collection->destroy();
    }
    
    public function test_Zip_destroy(): void
    {
        $stream = Stream::from([3 => 'a', 2 => 'b', 1 => 'c'])->zip(['n' => 4, 9 => 'e']);
        
        self::assertSame([
            3 => ['a', 4],
            2 => ['b', 'e'],
            1 => ['c', null],
        ], $stream->toArrayAssoc());
        
        $stream->destroy();
    }
    
    public function test_CombinedGeneral_destroy(): void
    {
        $stream = Producers::combinedFrom(static fn(): array => ['a', 2], [0, '2'])->stream();
        
        self::assertSame(['a' => 0, 2 => '2'], $stream->toArrayAssoc());
        
        $stream->destroy();
    }
    
    public function test_CombinedArrays_destroy(): void
    {
        $stream = Producers::combinedFrom(['a', 2], [0, '2'])->stream();
        
        self::assertSame(['a' => 0, 2 => '2'], $stream->toArrayAssoc());
        
        $stream->destroy();
    }
    
    public function test_SendToMany_destroy(): void
    {
        $counter1 = Consumers::counter();
        $counter2 = Consumers::counter();
        
        $stream = Stream::from([1, 2, 3])->call($counter1, $counter2);
        $stream->run();
        
        self::assertSame($counter1->get(), $counter2->get());
        
        $stream->destroy();
    }
    
    public function test_Window_destroy(): void
    {
        $stream = Stream::from(['a', 'b', 'c'])->window(2);
        
        self::assertSame([
            [0 => 'a', 'b'],
            [1 => 'b', 'c'],
        ], $stream->toArray());
        
        $stream->destroy();
    }
    
    public function test_SingleItemBuffer_destroy(): void
    {
        //given
        $buffer = new SingleItemBuffer();
        self::assertSame(0, $buffer->count());
        
        //when
        $buffer->hold(new Item(3, 5));
        //then
        self::assertSame(1, $buffer->count());
        
        //when
        $buffer->destroy();
        //then
        self::assertSame(0, $buffer->count());
    }
    
    /**
     * @return Item[]
     */
    private static function convertToItems(array $data): array
    {
        $items = [];
        
        foreach ($data as $key => $value) {
            $items[] = new Item($key, $value);
        }
        
        return $items;
    }
}