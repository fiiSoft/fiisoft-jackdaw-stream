<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use ArrayIterator;
use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Operation\Internal\AssertionFailed;
use FiiSoft\Jackdaw\Producer\Generator\SequentialInt;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\StreamMaker;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StreamMakerTest extends TestCase
{
    private StreamMaker $stream;
    
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
        self::assertSame(
            '[{"name":"Bob"}]',
            StreamMaker::from([['name' => 'Bob']])->onlyWith(['name'])->toJsonAssoc()
        );
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
        $result = StreamMaker::from([['a' => 1], ['a' => null]])->complete('a', 0)->toArray();
        self::assertSame([['a' => 1], ['a' => 0]], $result);
    }
    
    public function test_moveTo_creates_array(): void
    {
        self::assertSame([
            ['num' => 1],
            ['num' => 2],
            ['num' => 3],
            ['num' => 4],
        ], $this->stream->moveTo('num')->toArray());
    }
    
    public function test_tail(): void
    {
        self::assertSame([3, 4], $this->stream->tail(2)->toArray());
    }
    
    public function test_best(): void
    {
        self::assertSame([1, 2], $this->stream->best(2)->toArray());
    }
    
    public function test_worst(): void
    {
        self::assertSame([4, 3], $this->stream->worst(2)->toArray());
    }
    
    public function test_mapField(): void
    {
        $result = StreamMaker::from([['a' => 5], ['a' => 2]])
            ->mapField('a', static fn(int $v): int => $v * 2)
            ->toArrayAssoc();
        
        self::assertSame([['a' => 10], ['a' => 4]], $result);
    }
    
    public function test_mapFieldWhen(): void
    {
        $result = StreamMaker::from([['a' => 5, 'b' => 'foo'], ['a' => 2, 'b' => 'bar']])
            ->mapFieldWhen('a', 'is_int', static fn(int $v): int => $v * 2)
            ->mapFieldWhen('b', 'is_string', 'strrev')
            ->toArrayAssoc();
        
        self::assertSame([['a' => 10, 'b' => 'oof'], ['a' => 4, 'b' => 'rab']], $result);
    }
    
    public function test_castToFloat(): void
    {
        self::assertSame([1.0, 2.0], $this->stream->castToFloat()->limit(2)->toArray());
    }
    
    public function test_castToString(): void
    {
        self::assertSame(['1', '2'], $this->stream->castToString()->limit(2)->toArray());
    }
    
    public function test_castToBool(): void
    {
        self::assertSame([false, true], StreamMaker::from([0, 1])->castToBool()->toArray());
    }
    
    public function test_onError_with_ErrorHandler(): void
    {
        $result = $this->stream->onError(OnError::skip())->callOnce(static function () {
            throw new \RuntimeException('error');
        });
        
        self::assertSame([2, 3, 4], $result->toArray());
    }
    
    public function test_onError_with_callable(): void
    {
        $result = $this->stream->onError(static fn(): bool => true)->callOnce(static function () {
            throw new \RuntimeException('error');
        });
        
        self::assertSame([2, 3, 4], $result->toArray());
    }
    
    public function test_onError_allows_to_replace_existing_error_handler(): void
    {
        $stream = $this->stream->onError(OnError::skip())->onError(OnError::abort(), true);
        
        $result = $stream->callOnce(static function () {
            throw new \RuntimeException('error');
        });
    
        self::assertSame([], $result->toArray());
    }
    
    public function test_onError_throws_exception_when_handler_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param handler');
        
        $this->stream->onError('wrong argument');
    }
    
    public function test_onSuccess(): void
    {
        $onSuccessHandlerCalled = false;
        
        $this->stream->onSuccess(static function () use (&$onSuccessHandlerCalled) {
            $onSuccessHandlerCalled = true;
        })->run();
        
        self::assertTrue($onSuccessHandlerCalled);
    }
    
    public function test_onSuccess_allows_to_replace_handler(): void
    {
        $onSuccessHandlerCalled = 0;
    
        $handler = static function () use (&$onSuccessHandlerCalled) {
            ++$onSuccessHandlerCalled;
        };
        
        $this->stream->onSuccess($handler)->onSuccess($handler, true)->run();
        
        self::assertSame(1, $onSuccessHandlerCalled);
    }
    
    public function test_onFinish(): void
    {
        $onFinishHandlerCalled = false;
        
        $this->stream->onFinish(static function () use (&$onFinishHandlerCalled) {
            $onFinishHandlerCalled = true;
        })->run();
        
        self::assertTrue($onFinishHandlerCalled);
    }
    
    public function test_onFinish_allows_to_replace_handler(): void
    {
        $onFinishHandlerCalled = 0;
    
        $handler = static function () use (&$onFinishHandlerCalled) {
            ++$onFinishHandlerCalled;
        };
        
        $this->stream->onFinish($handler)->onFinish($handler, true)->run();
        
        self::assertSame(1, $onFinishHandlerCalled);
    }
    
    public function test_when_ErrorHandler_returns_null_then_exception_is_rethrown(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('error');
    
        $this->stream->onError(static fn() => null)->callOnce(static function () {
            throw new \RuntimeException('error');
        })->run();
    }
    
    public function test_concat(): void
    {
        $stream = StreamMaker::of([[1,2]], [[3,4]]);
        
        self::assertSame(['1,2', '3,4'], $stream->concat(',')->toArray());
        self::assertSame(['1 2', '3 4'], $stream->concat(' ')->toArray());
    }
    
    public function test_tokenize(): void
    {
        $result = StreamMaker::from(['first string', 'second string'])->tokenize()->toArray();
        
        self::assertSame(['first', 'string', 'second', 'string'], $result);
    }
    
    public function test_loop(): void
    {
        $this->expectException(RuntimeException::class);
        
        StreamMaker::from([1])
            ->feed(StreamMaker::from([1])->loop())
            ->callOnce(static function () {
                throw new \RuntimeException();
            })
            ->run();
    }
    
    public function test_trim(): void
    {
        self::assertSame('first,second', StreamMaker::from([' first', ' second '])->trim()->toString());
    }
    
    public function test_assert(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('Element does not satisfy expectations. Mode: 1, value: 3, key: 2');
        
        $this->stream->assert(Filters::lessThan(3))->run();
    }
    
    public function test_rename(): void
    {
        $result = StreamMaker::from([['a' => 5, 'b' => 'foo']])
            ->rename('a', 'aaa')
            ->rename('b', 'bbb')
            ->toArray();
        
        self::assertSame([['aaa' => 5, 'bbb' => 'foo']], $result);
    }
    
    public function test_remap(): void
    {
        $result = StreamMaker::from([['a' => 5, 'b' => 'foo']])
            ->remap(['a' => 'aaa', 'b' => 'bbb'])
            ->toArray();
        
        self::assertSame([['aaa' => 5, 'bbb' => 'foo']], $result);
    }
    
    public function test_omitBy(): void
    {
        $result = StreamMaker::from([['a' => 5, 'b' => 'foo'], ['a' => 3, 'b' => null]])
            ->omitBy('b', 'is_null')
            ->toArray();
        
        self::assertSame([['a' => 5, 'b' => 'foo']], $result);
    }
    
    public function test_extractWhen(): void
    {
        $result = StreamMaker::from([['a' => 5, 'b' => 'foo'], ['a' => 3, 'b' => null]])
            ->extractWhen('is_int')
            ->toArray();
        
        self::assertSame([['a' => 5], ['a' => 3]], $result);
    }
    
    public function test_removeWhen(): void
    {
        $result = StreamMaker::from([['a' => 5, 'b' => 'foo'], ['a' => 3, 'b' => null]])
            ->removeWhen('is_int')
            ->toArray();
        
        self::assertSame([['b' => 'foo'], ['b' => null]], $result);
    }
    
    public function test_create_from_Result(): void
    {
        $sum = $this->stream->reduce('array_sum');
        
        self::assertSame([10], StreamMaker::from($sum)->toArray());
        self::assertSame([10], StreamMaker::from($sum)->toArray());
        
        self::assertSame([10], StreamMaker::of($sum)->toArray());
        self::assertSame([10], StreamMaker::of($sum)->toArray());
        
        self::assertSame([10], StreamMaker::empty()->join($sum)->toArray());
        self::assertSame([10], StreamMaker::empty()->join($sum)->toArray());
    }
    
    public function test_gather(): void
    {
        $result = null;
        
        $this->stream->gather()->call(static function (array $all) use (&$result) {
            $result = $all;
        })->run();
        
        self::assertSame([1,2,3,4], $result);
    }
    
    public function test_collect_while(): void
    {
        self::assertSame([1, 2], $this->stream->collectWhile(Filters::lessThan(3))->toArray());
    }
    
    public function test_collect_until(): void
    {
        self::assertSame([1, 2], $this->stream->collectUntil(Filters::greaterThan(2))->toArray());
    }
    
    public function test_make_tuple(): void
    {
        self::assertSame(
            '[[0,1],[1,2],[2,3],[3,4]]',
            $this->stream->makeTuple()->toJson()
        );
        
        self::assertSame(
            '[{"key":0,"value":1},{"key":1,"value":2},{"key":2,"value":3},{"key":3,"value":4}]',
            $this->stream->makeTuple(true)->toJson()
        );
    }
    
    public function test_gather_while(): void
    {
        self::assertSame('[[1,2]]', $this->stream->gatherWhile(Filters::lessThan(3))->toJson());
        self::assertSame('[[1,2]]', $this->stream->gatherWhile(Filters::lessThan(3), Check::VALUE, true)->toJson());
    }
    
    public function test_gather_until(): void
    {
        self::assertSame('[[1,2]]', $this->stream->gatherUntil(Filters::greaterThan(2))->toJson());
        self::assertSame('[[1,2]]', $this->stream->gatherUntil(Filters::greaterThan(2), Check::VALUE, true)->toJson());
    }
    
    public function test_reindexBy(): void
    {
        $result = StreamMaker::from([['a' => 5, 'b' => 'foo'], ['a' => 3, 'b' => 'bar']])
            ->reindexBy('a')
            ->extract('b')
            ->toArrayAssoc();
        
        self::assertSame([5 => 'foo', 3 => 'bar'], $result);
    }
    
    public function test_mapKeyValue(): void
    {
        $result = StreamMaker::from([['id' => 5, 'name' => 'John'], ['id' => 3, 'name' => 'Susan']])
            ->mapKV(static fn(array $row): array => [$row['id'] => $row['name']])
            ->mapKV(static fn(string $name, int $id): array => [$name => $id]) //this is equivalent of flip()
            ->toArrayAssoc();
    
        self::assertSame(['John' => 5, 'Susan' => 3], $result);
    }
    
    public function test_reindexBy_field_and_remove_field_from_element(): void
    {
        $result = StreamMaker::from([['id' => 5, 'name' => 'John'], ['id' => 3, 'name' => 'Susan']])
            ->reindexBy('id', true)
            ->toArrayAssoc();
    
        self::assertSame([
            5 => ['name' => 'John'],
            3 => ['name' => 'Susan'],
        ], $result);
    }
}