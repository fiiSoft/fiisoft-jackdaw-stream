<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use ArrayIterator;
use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\StreamApi;
use FiiSoft\Jackdaw\Producer\Generator\SequentialInt;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\StreamMaker;
use PHPUnit\Framework\TestCase;

class StreamMakerTest extends TestCase
{
    private StreamApi $stream;
    
    protected function setUp(): void
    {
        $this->stream = StreamMaker::from([1, 2, 3, 4]);
    }
    
    public function test_from_array(): void
    {
        $stream = StreamMaker::from([6,2,4,8]);
        
        self::assertSame([6, 2, 4, 8], $stream->toArray());
        self::assertSame('6,2,4,8', $stream->toString());
    }
    
    public function test_from_Iterator(): void
    {
        $stream = StreamMaker::from(new ArrayIterator([6, 2, 4, 8]));
    
        self::assertSame([6, 2, 4, 8], $stream->toArray());
        self::assertSame('6,2,4,8', $stream->toString());
    }
    
    public function test_from_Producer(): void
    {
        $producer = new SequentialInt(1, 1, 4);
        $stream = StreamMaker::from($producer);
    
        self::assertSame([1, 2, 3, 4], $stream->toArray());
        self::assertSame('1,2,3,4', $stream->toString());
    }
    
    public function test_from_callable_StreamApi_factory(): void
    {
        $stream = StreamMaker::from(static fn() => Stream::from([5, 3, 1]));
    
        self::assertSame([5, 3, 1], $stream->toArray());
        self::assertSame('5,3,1', $stream->toString());
    }
    
    public function test_make_with_method_of(): void
    {
        $stream = StreamMaker::of(['a', 'b'], 1, 2, ['c', 'd']);
        
        self::assertSame('a,b,1,2,c,d', $stream->toString());
        self::assertSame('a,b,1,2,c,d', $stream->toString());
    }
    
    public function test_make_empty_stream(): void
    {
        $stream = StreamMaker::empty();
        
        self::assertSame('', $stream->toString());
        self::assertSame('', $stream->toString());
    }
    
    public function test_wrog_factory_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        StreamMaker::from('yolo');
    }
    
    public function test_notNull(): void
    {
        self::assertTrue($this->stream->notNull()->isNotEmpty()->get());
    }
    
    public function test_lessOrEqual(): void
    {
        self::assertSame([1, 2], $this->stream->lessOrEqual(2)->toArray());
    }
    
    public function test_skip(): void
    {
        self::assertSame([3, 4], $this->stream->skip(2)->toArray());
    }
    
    public function test_call(): void
    {
        $counter = Consumers::counter();
        $this->stream->call($counter)->run();
    
        self::assertSame(4, $counter->count());
    }
    
    public function test_notEmpty(): void
    {
        self::assertSame(4, $this->stream->notEmpty()->count()->get());
    }
    
    public function test_without(): void
    {
        self::assertSame([1, 4], $this->stream->without([2, 3])->toArray());
    }
    
    public function test_lessThan(): void
    {
        self::assertSame('1', $this->stream->lessThan(2)->toString());
    }
    
    public function test_greaterOrEqual(): void
    {
        self::assertSame([3, 4], $this->stream->greaterOrEqual(3)->toArray());
    }
    
    public function test_onlyNumeric(): void
    {
        self::assertSame(4, $this->stream->onlyNumeric()->count()->get());
    }
    
    public function test_onlyIntegers(): void
    {
        self::assertSame(4, $this->stream->onlyIntegers()->count()->get());
    }
    
    public function test_onlyStrings(): void
    {
        self::assertSame(0, $this->stream->onlyStrings()->count()->get());
    }
    
    public function test_map(): void
    {
        self::assertSame([1, 4, 9, 16], $this->stream->map(static fn(int $v) => $v * $v)->toArray());
    }
    
    public function test_mapKey(): void
    {
        $expected = ['_0' => 1, '_1' => 2, '_2' => 3, '_3' => 4];
        self::assertSame($expected, $this->stream->mapKey(static fn($_, $k) => '_'.$k)->toArrayAssoc());
    }
    
    public function test_castToInt(): void
    {
        self::assertSame([1, 2, 3, 4], $this->stream->castToInt()->toArray());
    }
    
    public function test_greaterThan(): void
    {
        self::assertSame([4], $this->stream->greaterThan(3)->toArray());
    }
    
    public function test_collectKeys(): void
    {
        $buffer = new \ArrayObject();
        $this->stream->collectKeys($buffer)->run();
        
        self::assertSame([0, 1, 2, 3], $buffer->getArrayCopy());
    }
    
    public function test_only(): void
    {
        self::assertSame([2, 3], $this->stream->only([2, 3])->toArray());
    }
    
    public function test_omit(): void
    {
        self::assertSame([1, 2], $this->stream->omit(Filters::greaterOrEqual(3))->toArray());
    }
    
    public function test_join(): void
    {
        self::assertSame([1, 2, 3, 4, 'a', 'b', 'c'], $this->stream->join(['a', 'b', 'c'])->toArray());
    }
    
    public function test_unique(): void
    {
        self::assertSame([1, 3, 5, 2], StreamMaker::from([1, 3, 5, 1, 2, 5])->unique()->toArray());
    }
    
    public function test_reindex(): void
    {
        $actual = StreamMaker::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->reindex()->toArrayAssoc();
        self::assertSame([1, 2, 3, 4], $actual);
    }
    
    public function test_flip(): void
    {
        self::assertSame([1 => 0, 2 => 1, 3 => 2, 4 => 3], $this->stream->flip()->toArrayAssoc());
    }
    
    public function test_chunk(): void
    {
        self::assertSame([[1], [2], [3], [4]], $this->stream->chunk(1)->toArray());
        self::assertSame([[1, 2], [3, 4]], $this->stream->chunk(2)->toArray());
        self::assertSame([[1, 2, 3], [4]], $this->stream->chunk(3)->toArray());
        self::assertSame([[1, 2, 3, 4]], $this->stream->chunk(4)->toArray());
        self::assertSame([[1, 2, 3, 4]], $this->stream->chunk(5)->toArray());
    }
    
    public function test_chunkAssoc(): void
    {
        $stream = StreamMaker::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $expected = [['a' => 1, 'b' => 2], ['c' => 3, 'd' => 4]];
        self::assertSame($expected, $stream->chunk(2, true)->toArrayAssoc());
        self::assertSame($expected, $stream->chunkAssoc(2)->toArrayAssoc());
    }
    
    public function test_extract(): void
    {
        $rows = [['id' => 1, 'name' => 'Joel'], ['id' => 2, 'name' => 'Grim']];
        self::assertSame(['Joel', 'Grim'], StreamMaker::from($rows)->extract('name')->toArray());
    }
    
    public function test_split(): void
    {
        $sentences = [
            'cedrium devirginato et adelphis',
            'nunquam dignus habena',
        ];
        
        $expected = [
            ['cedrium', 'devirginato', 'et', 'adelphis'],
            ['nunquam', 'dignus', 'habena'],
        ];
        
        self::assertSame($expected, StreamMaker::from($sentences)->split()->toArray());
    }
    
    public function test_scan(): void
    {
        self::assertSame([0, 1, 3, 6, 10], $this->stream->scan(0, Reducers::sum())->toArray());
    }
    
    public function test_flat(): void
    {
        $stream = StreamMaker::from([
            ['a' => 1],
            'b',
            ['c' => ['d' => 2]]
        ]);
        
        self::assertSame([1, 'b', 2], $stream->flat()->toArray());
    }
    
    public function test_flatMap(): void
    {
        $actual = $this->stream->flatMap(static fn($v, $k) => [$k => [$v]])->toArray();
        self::assertSame([1, 2, 3, 4], $actual);
    }
    
    public function test_sort(): void
    {
        self::assertSame([1, 2, 3, 4], $this->stream->sort()->toArray());
    }
    
    public function test_reverse_sort(): void
    {
        self::assertSame([4, 3, 2, 1], $this->stream->rsort()->toArray());
    }
    
    public function test_reverse(): void
    {
        self::assertSame([4, 3, 2, 1], $this->stream->reverse()->toArray());
    }
    
    public function test_shuffle(): void
    {
        self::assertSame(4, $this->stream->shuffle()->count()->get());
    }
    
    public function test_feed(): void
    {
        //given
        $buffer = new \ArrayObject();
        $stream = Stream::empty()->collectIn($buffer);
        
        //when
        $this->stream->feed($stream)->run();
        
        //then
        self::assertSame([1, 2, 3, 4], $buffer->getArrayCopy());
    }
    
    public function test_until(): void
    {
        $counter = Consumers::counter();
        $this->stream->until(static fn($v) => $v > 2)->call($counter)->run();
        
        self::assertSame(2, $counter->count());
    }
    
    public function test_while(): void
    {
        self::assertSame([1, 2], $this->stream->while(Filters::lessOrEqual(2))->toArray());
    }
    
    public function test_forEach(): void
    {
        $counter = Consumers::counter();
        $this->stream->forEach($counter);
        
        self::assertSame(4, $counter->count());
    }
    
    public function test_reduce(): void
    {
        self::assertSame(10, $this->stream->reduce(Reducers::sum())->get());
    }
    
    public function test_fold(): void
    {
        self::assertSame(13, $this->stream->fold(13, Reducers::max())->get());
    }
    
    public function test_groupBy(): void
    {
        self::assertSame(['odd', 'even'], $this->stream->groupBy(Discriminators::evenOdd())->classifiers());
    }
    
    public function test_isNotEmpty(): void
    {
        self::assertTrue($this->stream->isNotEmpty()->get());
    }
    
    public function test_has(): void
    {
        self::assertTrue($this->stream->has(3)->get());
    }
    
    public function test_hasAny(): void
    {
        self::assertTrue($this->stream->hasAny([8,2,5])->get());
    }
    
    public function test_hasEvery(): void
    {
        self::assertTrue($this->stream->hasEvery([1, 4])->get());
    }
    
    public function test_hasOnly(): void
    {
        self::assertTrue($this->stream->hasOnly([6, 3, 2, 5, 1, 4])->get());
    }
    
    public function test_find(): void
    {
        $item = $this->stream->find(3);
        
        self::assertTrue($item->found());
        self::assertSame(2, $item->key());
        self::assertSame(3, $item->get());
    }
    
    public function test_toArrayAssoc(): void
    {
        $intputData = ['a' => 1, 'b' => 2];
        
        self::assertSame($intputData, StreamMaker::from($intputData)->toArray(true));
        self::assertSame($intputData, StreamMaker::from($intputData)->toArrayAssoc());
    }
    
    public function test_toJsonAssoc(): void
    {
        $inputData = ['a' => 1, 'b' => 2];
        $expected = '{"a":1,"b":2}';
        
        self::assertSame($expected, StreamMaker::from($inputData)->toJson(0, true));
        self::assertSame($expected, StreamMaker::from($inputData)->toJsonAssoc());
    }
    
    public function test_first(): void
    {
        self::assertSame(1, $this->stream->first()->get());
    }
    
    public function test_last(): void
    {
        self::assertSame(4, $this->stream->last()->get());
    }
    
    public function test_run(): void
    {
        $this->stream->run();
        self::assertTrue(true);
    }
    
    public function test_filterBy(): void
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe'],
            ['id' => 5, 'name' => 'Christine'],
        ]);
        
        $actual = $stream->filterBy('name', Filters::length()->ge(5))->toArray();
        self::assertSame([['id' => 5, 'name' => 'Christine']], $actual);
    }
    
    public function test_sortBy(): void
    {
        $actual = StreamMaker::from([['a' => 1], ['a' => 5]])->sortBy('a desc')->toArray();
        self::assertSame([['a' => 5], ['a' => 1]], $actual);
    }
    
    public function test_remove(): void
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe'],
            ['id' => 5, 'name' => 'Christine'],
        ]);
        
        self::assertSame([['name' => 'Joe'], ['name' => 'Christine']], $stream->remove('id')->toArray());
    }
    
    public function test_append(): void
    {
        $actual = $this->stream->append('doubled', static fn(int $v) => 2 * $v)->toArray();
        self::assertSame([
            [0 => 1, 'doubled' => 2],
            [1 => 2, 'doubled' => 4],
            [2 => 3, 'doubled' => 6],
            [3 => 4, 'doubled' => 8],
        ], $actual);
    }
    
    public function test_collect(): void
    {
        self::assertSame([1, 2, 3, 4], $this->stream->collect()->get());
    }
    
    public function test_collectIn(): void
    {
        $collector = Collectors::default();
        $this->stream->collectIn($collector)->run();
        
        self::assertSame([1, 2, 3, 4], $collector->getArrayCopy());
    }
    
    public function test_aggregate(): void
    {
        self::assertSame('[{"1":2,"3":4}]', $this->stream->aggregate([1, 3])->toJsonAssoc());
    }
    
    public function test_onlyWith(): void
    {
        self::assertSame('[{"name":"Bob"}]', Stream::from([['name' => 'Bob'],])->onlyWith(['name'])->toJsonAssoc());
    }
    
    public function test_callOnce(): void
    {
        $counter = Consumers::counter();
        $this->stream->callOnce($counter)->run();
        
        self::assertSame(1, $counter->count());
    }
    
    public function test_callMax(): void
    {
        $this->stream->callMax(2, $counter = Consumers::counter())->run();
        self::assertSame(2, $counter->count());
    }
    
    public function test_callWhen(): void
    {
        $this->stream->callWhen(Filters::lessOrEqual(2), $counter = Consumers::counter())->run();
        self::assertSame(2, $counter->count());
    }
    
    public function test_mapWhen(): void
    {
        $result = $this->stream->mapWhen(Filters::greaterThan(2), static function (int $n) {
            return $n * 2;
        })->toArray();
        
        self::assertSame([1, 2, 6, 8], $result);
    }
    
    public function test_complete(): void
    {
        $result = Stream::from([['a' => 1], ['a' => null]])->complete('a', 0)->toArray();
        self::assertSame([['a' => 1], ['a' => 0]], $result);
    }
}