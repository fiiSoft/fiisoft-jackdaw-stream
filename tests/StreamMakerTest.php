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
    /** @var StreamApi */
    private $stream;
    
    protected function setUp()
    {
        $this->stream = StreamMaker::from([1, 2, 3, 4]);
    }
    
    public function test_from_array()
    {
        $stream = StreamMaker::from([6,2,4,8]);
        
        self::assertSame([6, 2, 4, 8], $stream->toArray());
        self::assertSame('6,2,4,8', $stream->toString());
    }
    
    public function test_from_Iterator()
    {
        $stream = StreamMaker::from(new ArrayIterator([6, 2, 4, 8]));
    
        self::assertSame([6, 2, 4, 8], $stream->toArray());
        self::assertSame('6,2,4,8', $stream->toString());
    }
    
    public function test_from_Producer()
    {
        $producer = new SequentialInt(1, 1, 4);
        $stream = StreamMaker::from($producer);
    
        self::assertSame([1, 2, 3, 4], $stream->toArray());
        self::assertSame('1,2,3,4', $stream->toString());
    }
    
    public function test_from_callable_StreamApi_factory()
    {
        $stream = StreamMaker::from(static function () {
            return Stream::from([5, 3, 1]);
        });
    
        self::assertSame([5, 3, 1], $stream->toArray());
        self::assertSame('5,3,1', $stream->toString());
    }
    
    public function test_make_with_method_of()
    {
        $stream = StreamMaker::of(['a', 'b'], 1, 2, ['c', 'd']);
        
        self::assertSame('a,b,1,2,c,d', $stream->toString());
        self::assertSame('a,b,1,2,c,d', $stream->toString());
    }
    
    public function test_make_empty_stream()
    {
        $stream = StreamMaker::empty();
        
        self::assertSame('', $stream->toString());
        self::assertSame('', $stream->toString());
    }
    
    public function test_wrog_factory_param()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        StreamMaker::from('yolo');
    }
    
    public function test_notNull()
    {
        self::assertTrue($this->stream->notNull()->isNotEmpty()->get());
    }
    
    public function test_lessOrEqual()
    {
        self::assertSame([1, 2], $this->stream->lessOrEqual(2)->toArray());
    }
    
    public function test_skip()
    {
        self::assertSame([3, 4], $this->stream->skip(2)->toArray());
    }
    
    public function test_call()
    {
        $counter = Consumers::counter();
        $this->stream->call($counter)->run();
    
        self::assertSame(4, $counter->count());
    }
    
    public function test_notEmpty()
    {
        self::assertSame(4, $this->stream->notEmpty()->count()->get());
    }
    
    public function test_without()
    {
        self::assertSame([1, 4], $this->stream->without([2, 3])->toArray());
    }
    
    public function test_lessThan()
    {
        self::assertSame('1', $this->stream->lessThan(2)->toString());
    }
    
    public function test_greaterOrEqual()
    {
        self::assertSame([3, 4], $this->stream->greaterOrEqual(3)->toArray());
    }
    
    public function test_onlyNumeric()
    {
        self::assertSame(4, $this->stream->onlyNumeric()->count()->get());
    }
    
    public function test_onlyIntegers()
    {
        self::assertSame(4, $this->stream->onlyIntegers()->count()->get());
    }
    
    public function test_onlyStrings()
    {
        self::assertSame(0, $this->stream->onlyStrings()->count()->get());
    }
    
    public function test_map()
    {
        self::assertSame([1, 4, 9, 16], $this->stream->map(static function (int $v) {
            return $v * $v;
        })->toArray());
    }
    
    public function test_mapKey()
    {
        $expected = ['_0' => 1, '_1' => 2, '_2' => 3, '_3' => 4];
        
        self::assertSame($expected, $this->stream->mapKey(static function ($_, $k) {
            return '_'.$k;
        })->toArrayAssoc());
    }
    
    public function test_castToInt()
    {
        self::assertSame([1, 2, 3, 4], $this->stream->castToInt()->toArray());
    }
    
    public function test_greaterThan()
    {
        self::assertSame([4], $this->stream->greaterThan(3)->toArray());
    }
    
    public function test_collectKeys()
    {
        $buffer = new \ArrayObject();
        $this->stream->collectKeys($buffer)->run();
        
        self::assertSame([0, 1, 2, 3], $buffer->getArrayCopy());
    }
    
    public function test_only()
    {
        self::assertSame([2, 3], $this->stream->only([2, 3])->toArray());
    }
    
    public function test_omit()
    {
        self::assertSame([1, 2], $this->stream->omit(Filters::greaterOrEqual(3))->toArray());
    }
    
    public function test_join()
    {
        self::assertSame([1, 2, 3, 4, 'a', 'b', 'c'], $this->stream->join(['a', 'b', 'c'])->toArray());
    }
    
    public function test_unique()
    {
        self::assertSame([1, 3, 5, 2], StreamMaker::from([1, 3, 5, 1, 2, 5])->unique()->toArray());
    }
    
    public function test_reindex()
    {
        $actual = StreamMaker::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->reindex()->toArrayAssoc();
        self::assertSame([1, 2, 3, 4], $actual);
    }
    
    public function test_flip()
    {
        self::assertSame([1 => 0, 2 => 1, 3 => 2, 4 => 3], $this->stream->flip()->toArrayAssoc());
    }
    
    public function test_chunk()
    {
        self::assertSame([[1], [2], [3], [4]], $this->stream->chunk(1)->toArray());
        self::assertSame([[1, 2], [3, 4]], $this->stream->chunk(2)->toArray());
        self::assertSame([[1, 2, 3], [4]], $this->stream->chunk(3)->toArray());
        self::assertSame([[1, 2, 3, 4]], $this->stream->chunk(4)->toArray());
        self::assertSame([[1, 2, 3, 4]], $this->stream->chunk(5)->toArray());
    }
    
    public function test_chunkAssoc()
    {
        $stream = StreamMaker::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $expected = [['a' => 1, 'b' => 2], ['c' => 3, 'd' => 4]];
        self::assertSame($expected, $stream->chunk(2, true)->toArrayAssoc());
        self::assertSame($expected, $stream->chunkAssoc(2)->toArrayAssoc());
    }
    
    public function test_extract()
    {
        $rows = [['id' => 1, 'name' => 'Joel'], ['id' => 2, 'name' => 'Grim']];
        self::assertSame(['Joel', 'Grim'], StreamMaker::from($rows)->extract('name')->toArray());
    }
    
    public function test_split()
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
    
    public function test_scan()
    {
        self::assertSame([0, 1, 3, 6, 10], $this->stream->scan(0, Reducers::sum())->toArray());
    }
    
    public function test_flat()
    {
        $stream = StreamMaker::from([
            ['a' => 1],
            'b',
            ['c' => ['d' => 2]]
        ]);
        
        self::assertSame([1, 'b', 2], $stream->flat()->toArray());
    }
    
    public function test_flatMap()
    {
        $actual = $this->stream->flatMap(static function ($v, $k) {
            return [$k => [$v]];
        })->toArray();
        
        self::assertSame([1, 2, 3, 4], $actual);
    }
    
    public function test_sort()
    {
        self::assertSame([1, 2, 3, 4], $this->stream->sort()->toArray());
    }
    
    public function test_reverse_sort()
    {
        self::assertSame([4, 3, 2, 1], $this->stream->rsort()->toArray());
    }
    
    public function test_reverse()
    {
        self::assertSame([4, 3, 2, 1], $this->stream->reverse()->toArray());
    }
    
    public function test_shuffle()
    {
        self::assertSame(4, $this->stream->shuffle()->count()->get());
    }
    
    public function test_feed()
    {
        //given
        $buffer = new \ArrayObject();
        $stream = Stream::empty()->collectIn($buffer);
        
        //when
        $this->stream->feed($stream)->run();
        
        //then
        self::assertSame([1, 2, 3, 4], $buffer->getArrayCopy());
    }
    
    public function test_until()
    {
        $counter = Consumers::counter();
        $this->stream->until(static function ($v) {
            return $v > 2;
        })->call($counter)->run();
        
        self::assertSame(2, $counter->count());
    }
    
    public function test_while()
    {
        self::assertSame([1, 2], $this->stream->while(Filters::lessOrEqual(2))->toArray());
    }
    
    public function test_forEach()
    {
        $counter = Consumers::counter();
        $this->stream->forEach($counter);
        
        self::assertSame(4, $counter->count());
    }
    
    public function test_reduce()
    {
        self::assertSame(10, $this->stream->reduce(Reducers::sum())->get());
    }
    
    public function test_fold()
    {
        self::assertSame(13, $this->stream->fold(13, Reducers::max())->get());
    }
    
    public function test_groupBy()
    {
        self::assertSame(['odd', 'even'], $this->stream->groupBy(Discriminators::evenOdd())->classifiers());
    }
    
    public function test_isNotEmpty()
    {
        self::assertTrue($this->stream->isNotEmpty()->get());
    }
    
    public function test_has()
    {
        self::assertTrue($this->stream->has(3)->get());
    }
    
    public function test_hasAny()
    {
        self::assertTrue($this->stream->hasAny([8,2,5])->get());
    }
    
    public function test_hasEvery()
    {
        self::assertTrue($this->stream->hasEvery([1, 4])->get());
    }
    
    public function test_hasOnly()
    {
        self::assertTrue($this->stream->hasOnly([6, 3, 2, 5, 1, 4])->get());
    }
    
    public function test_find()
    {
        $item = $this->stream->find(3);
        
        self::assertTrue($item->found());
        self::assertSame(2, $item->key());
        self::assertSame(3, $item->get());
    }
    
    public function test_toArrayAssoc()
    {
        $intputData = ['a' => 1, 'b' => 2];
        
        self::assertSame($intputData, StreamMaker::from($intputData)->toArray(true));
        self::assertSame($intputData, StreamMaker::from($intputData)->toArrayAssoc());
    }
    
    public function test_toJsonAssoc()
    {
        $inputData = ['a' => 1, 'b' => 2];
        $expected = '{"a":1,"b":2}';
        
        self::assertSame($expected, StreamMaker::from($inputData)->toJson(0, true));
        self::assertSame($expected, StreamMaker::from($inputData)->toJsonAssoc());
    }
    
    public function test_first()
    {
        self::assertSame(1, $this->stream->first()->get());
    }
    
    public function test_last()
    {
        self::assertSame(4, $this->stream->last()->get());
    }
    
    public function test_run()
    {
        $this->stream->run();
        self::assertTrue(true);
    }
    
    public function test_filterBy()
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe'],
            ['id' => 5, 'name' => 'Christine'],
        ]);
        
        $actual = $stream->filterBy('name', Filters::length()->ge(5))->toArray();
        self::assertSame([['id' => 5, 'name' => 'Christine']], $actual);
    }
    
    public function test_sortBy()
    {
        $actual = StreamMaker::from([['a' => 1], ['a' => 5]])->sortBy('a desc')->toArray();
        self::assertSame([['a' => 5], ['a' => 1]], $actual);
    }
    
    public function test_remove()
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe'],
            ['id' => 5, 'name' => 'Christine'],
        ]);
        
        self::assertSame([['name' => 'Joe'], ['name' => 'Christine']], $stream->remove('id')->toArray());
    }
    
    public function test_append()
    {
        $actual = $this->stream->append('doubled', static function (int $v) {
            return 2 * $v;
        })->toArray();
        
        self::assertSame([
            [0 => 1, 'doubled' => 2],
            [1 => 2, 'doubled' => 4],
            [2 => 3, 'doubled' => 6],
            [3 => 4, 'doubled' => 8],
        ], $actual);
    }
    
    public function test_collect()
    {
        self::assertSame([1, 2, 3, 4], $this->stream->collect()->get());
    }
    
    public function test_collectIn()
    {
        $collector = Collectors::default();
        $this->stream->collectIn($collector)->run();
        
        self::assertSame([1, 2, 3, 4], $collector->getArrayCopy());
    }
    
    public function test_aggregate()
    {
        self::assertSame('[{"1":2,"3":4}]', $this->stream->aggregate([1, 3])->toJsonAssoc());
    }
    
    public function test_onlyWith()
    {
        self::assertSame('[{"name":"Bob"}]', Stream::from([['name' => 'Bob'],])->onlyWith(['name'])->toJsonAssoc());
    }
    
    public function test_callOnce()
    {
        $counter = Consumers::counter();
        $this->stream->callOnce($counter)->run();
        
        self::assertSame(1, $counter->count());
    }
    
    public function test_callMax()
    {
        $this->stream->callMax(2, $counter = Consumers::counter())->run();
        self::assertSame(2, $counter->count());
    }
    
    public function test_callWhen()
    {
        $this->stream->callWhen(Filters::lessOrEqual(2), $counter = Consumers::counter())->run();
        self::assertSame(2, $counter->count());
    }
    
    public function test_mapWhen()
    {
        $result = $this->stream->mapWhen(Filters::greaterThan(2), static function (int $n) {
            return $n * 2;
        })->toArray();
        
        self::assertSame([1, 2, 6, 8], $result);
    }
}