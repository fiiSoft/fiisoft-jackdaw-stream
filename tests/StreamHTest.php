<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\FullMemo;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\IdleForkHandler;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Special\Assert\AssertionFailed;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StreamHTest extends TestCase
{
    public function test_mapper_skip_keep_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::skip(1))
            ->toArray();
        
        self::assertSame([
            ['b' => 'b', 'c' => 'c', 'd' => 'd'],
            ['f' => 'f', 'g' => 'g', 'h' => 'h'],
            ['j' => 'j', 'k' => 'k'],
        ], $result);
    }
    
    public function test_mapper_skip_reindex_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::skip(1, true))
            ->toArray();
        
        self::assertSame([
            ['b', 'c', 'd'],
            ['f', 'g', 'h'],
            ['j', 'k'],
        ], $result);
    }
    
    public function test_mapper_limit_keep_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::limit(3))
            ->toArray();
        
        self::assertSame([
            ['a' => 'a', 'b' => 'b', 'c' => 'c'],
            ['e' => 'e', 'f' => 'f', 'g' => 'g'],
            ['i' => 'i', 'j' => 'j', 'k' => 'k'],
        ], $result);
    }
    
    public function test_mapper_limit_reindex_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::limit(3, true))
            ->toArray();
        
        self::assertSame([
            ['a', 'b', 'c'],
            ['e', 'f', 'g'],
            ['i', 'j', 'k'],
        ], $result);
    }
    
    public function test_mapper_stack_skip_and_limit_with_slice_keep_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::skip(1))
            ->map(Mappers::limit(2))
            ->map(Mappers::slice(0, null, false))
            ->toArray();
        
        self::assertSame([
            ['b' => 'b', 'c' => 'c'],
            ['f' => 'f', 'g' => 'g'],
            ['j' => 'j', 'k' => 'k'],
        ], $result);
    }
    
    public function test_mapper_stack_skip_and_limit_with_slice_reindex_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::skip(1))
            ->map(Mappers::limit(2))
            ->map(Mappers::slice(0, null, true))
            ->toArray();
        
        self::assertSame([
            ['b', 'c'],
            ['f', 'g'],
            ['j', 'k'],
        ], $result);
    }
    
    public function test_slice_with_onerror_handler(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->onError(OnError::abort())
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::slice(1, 2, true))
            ->toArray();
        
        self::assertSame([
            ['b', 'c'],
            ['f', 'g'],
            ['j', 'k'],
        ], $result);
    }
    
    public function test_stack_mappers_slice_reindex_keys_and_keep_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::limit(3, true))
            ->map(Mappers::skip(1))
            ->toArray();
        
        self::assertSame([
            [1 => 'b', 'c'],
            [1 => 'f', 'g'],
            [1 => 'j', 'k'],
        ], $result);
    }
    
    public function test_two_slice_mappers_with_other_mappers_between_them(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::limit(3))
            ->map(Mappers::reindexKeys())
            ->map(Mappers::forEach('\strtoupper'))
            ->map(Mappers::skip(1))
            ->toArray();
        
        self::assertSame([
            [1 => 'B', 'C'],
            [1 => 'F', 'G'],
            [1 => 'J', 'K'],
        ], $result);
    }
    
    public function test_two_slice_mappers_with_other_mappers_between_them_with_onerror_handler(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->onError(OnError::abort())
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::limit(3))
            ->map(Mappers::reindexKeys())
            ->map(Mappers::forEach('\strtoupper'))
            ->map(Mappers::skip(1))
            ->toArray();
        
        self::assertSame([
            [1 => 'B', 'C'],
            [1 => 'F', 'G'],
            [1 => 'J', 'K'],
        ], $result);
    }
    
    public function test_stream_with_slice_mapper_and_onerror_handler(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->onError(OnError::abort())
            ->chunk(5)
            ->map(Mappers::slice(2, 2))
            ->notEmpty()
            ->toArray();
        
        self::assertSame([
            [2 => 'c', 'd'],
            [7 => 'h', 'i'],
        ], $result);
    }
    
    public function test_stream_with_stacked_slice_mappers_with_different_offset(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->chunk(5)
            ->map(Mappers::skip(2))
            ->map(Mappers::skip(1))
            ->notEmpty()
            ->toArray();
        
        self::assertSame([
            [3 => 'd', 'e'],
            [8 => 'i', 'j'],
        ], $result);
    }
    
    public function test_stream_skipNth_2(): void
    {
        self::assertSame(
            [1, 3, 5, 7, 9, 11, 13, 15],
            Stream::from([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])->skipNth(2)->toArray()
        );
    }
    
    public function test_stream_skipNth_3(): void
    {
        self::assertSame(
            [1, 2, 4, 5, 7, 8, 10, 11, 13, 14],
            Stream::from([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])->skipNth(3)->toArray()
        );
    }
    
    public function test_stream_skipNth_3_with_onerror_handler(): void
    {
        self::assertSame(
            [1, 2, 4, 5, 7, 8, 10, 11, 13, 14],
            Stream::from([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])
                ->onError(OnError::abort())
                ->skipNth(3)
                ->toArray()
        );
    }
    
    public function test_stream_skipNth_as_everyNth(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
        
        self::assertSame(
            Stream::from($data)->everyNth(2)->toArray(),
            Stream::from($data)->skipNth(2)->toArray()
        );
    }
    
    public function test_stream_stacked_skipNth_as_everyNth(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
        
        self::assertSame(
            Stream::from($data)->everyNth(3)->toArray(),
            Stream::from($data)->skipNth(3)->skipNth(2)->toArray()
        );
    }
    
    /**
     * @dataProvider getDataForTestStreamStackedTwoSkipNth
     */
    #[DataProvider('getDataForTestStreamStackedTwoSkipNth')]
    public function test_stream_stacked_two_skipNth(int $first, int $second, array $expected): void
    {
        self::assertSame(
            $expected,
            Stream::from([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])
                ->skipNth($first)
                ->skipNth($second)
                ->toArray()
        );
    }
    
    public static function getDataForTestStreamStackedTwoSkipNth(): array
    {
        return [
            //skipNth, skipNth, expected
            [3, 2, [1, 4, 7, 10, 13]],
            [3, 3, [1, 2, 5, 7, 10, 11, 14]],
            [4, 2, [1, 3, 6, 9, 11, 14]],
            [4, 3, [1, 2, 5, 6, 9, 10, 13, 14]],
            [4, 4, [1, 2, 3, 6, 7, 9, 11, 13, 14]],
            [4, 5, [1, 2, 3, 5, 7, 9, 10, 11, 14, 15]],
            [5, 2, [1, 3, 6, 8, 11, 13]],
            [5, 3, [1, 2, 4, 6, 8, 9, 12, 13]],
        ];
    }
    
    public function test_everyNth_skipNth_everyNth_skipNth(): void
    {
        $data = [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28,
            29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54,
        ];
        
        $result = Stream::from($data)
            ->everyNth(2)
            ->skipNth(2)
            ->everyNth(3)
            ->skipNth(3)
            ->skipNth(2)
            ->toArray();
        
        self::assertSame([1, 37], $result);
    }
    
    public function test_everyNth_skipNth_everyNth_skipNth_step_by_step(): void
    {
        $data = [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28,
            29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54,
        ];
        
        $data = Stream::from($data)->everyNth(2)->toArray();
        self::assertSame([
            1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 33, 35, 37, 39, 41, 43, 45, 47, 49, 51, 53
        ], $data);
        
        $data = Stream::from($data)->skipNth(2)->toArray();
        self::assertSame([1, 5, 9, 13, 17, 21, 25, 29, 33, 37, 41, 45, 49, 53], $data);
        
        $data = Stream::from($data)->everyNth(3)->toArray();
        self::assertSame([1, 13, 25, 37, 49], $data);
        
        $data = Stream::from($data)->skipNth(3)->toArray();
        self::assertSame([1, 13, 37, 49], $data);
        
        $data = Stream::from($data)->skipNth(2)->toArray();
        self::assertSame([1, 37], $data);
    }
    
    public function test_forkMatch_simple(): void
    {
        $result = Stream::from([6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9])
            ->forkMatch(
                Discriminators::evenOdd(),
                [
                    'even' => Reducers::min(),
                    'odd' => Reducers::max(),
                ]
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => 2,
            'odd' => 9,
        ], $result);
    }
    
    public function test_forkMatch_simple_with_onerror_handler(): void
    {
        $result = Stream::from([6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9])
            ->onError(OnError::abort())
            ->forkMatch(
                Discriminators::evenOdd(),
                [
                    'even' => Reducers::min(),
                    'odd' => Reducers::max(),
                ]
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => 2,
            'odd' => 9,
        ], $result);
    }
    
    public function test_forkMatch_sophisticated(): void
    {
        $discriminator = static function ($value): string {
            if (\is_string($value)) {
                return 'string';
            } elseif (\is_float($value)) {
                return 'float';
            } elseif (\is_int($value)) {
                return ($value & 1) === 0 ? 'even' : 'odd';
            } elseif (\is_bool($value)) {
                return 'bool';
            } else {
                return 'uknown';
            }
        };
        
        $result = Stream::from([4, 'a', 3, null, 15.6, 'b', 'c', 9.24, false, 7, 'd', 6, true, 2.5, 'e', 5])
            ->forkMatch(
                $discriminator,
                [
                    'even' => Reducers::min(),
                    'odd' => Reducers::max(),
                    'string' => Stream::empty()->map('strtoupper')->reduce(Reducers::concat('|')),
                    'float' => Collectors::values(),
                    'bool' => Memo::sequence(),
                ],
                new IdleForkHandler()
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => 4,
            'odd' => 7,
            'string' => 'A|B|C|D|E',
            'float' => [15.6, 9.24, 2.5],
            'bool' => [8 => false, 12 => true],
        ], $result);
    }
    
    public function test_forkMatch_sophisticated_with_onerror_handler(): void
    {
        $discriminator = static function ($value): string {
            if (\is_string($value)) {
                return 'string';
            } elseif (\is_float($value)) {
                return 'float';
            } elseif (\is_int($value)) {
                return ($value & 1) === 0 ? 'even' : 'odd';
            } elseif (\is_bool($value)) {
                return 'bool';
            } else {
                return 'uknown';
            }
        };
        
        $result = Stream::from([4, 'a', 3, null, 15.6, 'b', 'c', 9.24, false, 7, 'd', 6, true, 2.5, 'e', 5])
            ->onError(OnError::abort())
            ->forkMatch(
                $discriminator,
                [
                    'even' => Reducers::min(),
                    'odd' => Reducers::max(),
                    'string' => Stream::empty()->map('strtoupper')->reduce(Reducers::concat('|')),
                    'float' => Collectors::values(),
                    'bool' => Memo::sequence(),
                ],
                new IdleForkHandler()
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => 4,
            'odd' => 7,
            'string' => 'A|B|C|D|E',
            'float' => [15.6, 9.24, 2.5],
            'bool' => [8 => false, 12 => true],
        ], $result);
    }
    
    public function test_forkMatch_throws_exception_when_handler_is_missing(): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::handlerIsNotDefined('other'));
        
        Stream::from([5, 2, 3, 'a', 1, 4])
            ->forkMatch(static function ($value): string {
                if (\is_int($value)) {
                    return ($value & 1) === 0 ? 'even' : 'odd';
                } else {
                    return 'other';
                }
            }, [
                'even' => Collectors::values(),
                'odd' => Collectors::values(),
            ])
            ->run();
    }
    
    public function test_forkMatch_throws_exception_when_handler_is_missing_with_onerror_handler(): void
    {
        $result = Stream::from([5, 2, 3, 'a', 1, 4])
            ->onError(OnError::skip())
            ->forkMatch(static function ($value): string {
                if (\is_int($value)) {
                    return ($value & 1) === 0 ? 'even' : 'odd';
                } else {
                    return 'other';
                }
            }, [
                'even' => Collectors::values(),
                'odd' => Collectors::values(),
            ])
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => [2, 4],
            'odd' => [5, 3, 1],
        ], $result);
    }
    
    public function test_iterating_of_collectKey_returns_keys(): void
    {
        $keys = ['a', 'b', 'c', 'd', 'e', 'f'];
        
        $stream = Stream::from(\array_flip($keys))->collectKeys();
        
        self::assertSame($keys, \iterator_to_array($stream));
        self::assertSame($keys, $stream->toArrayAssoc());
        self::assertSame($keys, $stream->toArray());
        self::assertSame($keys, $stream->get());
        
        self::assertCount(\count($keys), $stream);
        
        $stream->destroy();
    }
    
    public function test_iterating_of_collectKey_with_onerror_handler(): void
    {
        $keys = ['a', 'b', 'c', 'd', 'e', 'f'];
        
        $stream = Stream::from(\array_flip($keys))->onError(OnError::abort())->collectKeys();
        
        self::assertSame($keys, \iterator_to_array($stream));
        self::assertSame($keys, $stream->toArrayAssoc());
        self::assertSame($keys, $stream->toArray());
        self::assertSame($keys, $stream->get());
        
        self::assertCount(\count($keys), $stream);
        
        $stream->destroy();
    }
    
    public function test_iterating_of_collectValues_returns_values_without_keys(): void
    {
        $data = [5 => 'a', 2 => 'b', 4 => 'c', 1 => 'd', 3 => 'e', 6 => 'f'];
        $values = \array_values($data);
        
        $stream = Stream::from($data)->collectValues();
        
        self::assertSame($values, \iterator_to_array($stream));
        self::assertSame($values, $stream->toArrayAssoc());
        self::assertSame($values, $stream->toArray());
        self::assertSame($values, $stream->get());
        
        self::assertCount(\count($values), $stream);
        
        $stream->destroy();
    }
    
    public function test_iterating_of_collectValues_with_onerror_handler(): void
    {
        $data = [5 => 'a', 2 => 'b', 4 => 'c', 1 => 'd', 3 => 'e', 6 => 'f'];
        $values = \array_values($data);
        
        $stream = Stream::from($data)->onError(OnError::abort())->collectValues();
        
        self::assertSame($values, \iterator_to_array($stream));
        self::assertSame($values, $stream->toArrayAssoc());
        self::assertSame($values, $stream->toArray());
        self::assertSame($values, $stream->get());
        
        self::assertCount(\count($values), $stream);
        
        $stream->destroy();
    }
    
    public function test_transform_result_into_iterator(): void
    {
        $data = ['a' => 3, 'b' => 4, 'c' => 1];
        
        $result = Stream::from($data)
            ->collect()
            ->transform(static fn(array $result): \Iterator => new \ArrayIterator($result));
        
        self::assertInstanceOf(\Iterator::class, $result->get());
        
        self::assertSame($data, \iterator_to_array($result));
        self::assertSame($data, \iterator_to_array($result));
    }
    
    public function test_consume_collect_iterate(): void
    {
        $stream = Stream::from(['e', 'f'])->reindex()->collect();
        
        $stream->consume(['a', 'b']);
        self::assertSame(['a', 'b', 'e', 'f'], \iterator_to_array($stream));
        
        $stream->consume(['c', 'd']);
        self::assertSame(['a', 'b', 'e', 'f', 'c', 'd'], \iterator_to_array($stream));
        
        $stream->consume(['g', 'h']);
        self::assertSame(['a', 'b', 'e', 'f', 'c', 'd', 'g', 'h'], $stream->toArray());
        
        $stream->consume(['i', 'j']);
        self::assertSame(['a', 'b', 'e', 'f', 'c', 'd', 'g', 'h', 'i', 'j'], $stream->get());
    }
    
    public function test_collector_consume_with_stacked_producer(): void
    {
        $stream = Stream::empty()->tokenize()->collectValues();
        $stream->consume(['a b c', 'd e']);
        
        self::assertSame(['a', 'b', 'c', 'd', 'e'], $stream->get());
    }
    
    public function test_iterate_stream_without_any_values_with_onerror_handler(): void
    {
        self::assertEmpty(\iterator_to_array(Stream::from(['a', 'b', 'c'])->onError(OnError::abort())->onlyIntegers()));
    }
    
    public function test_iterate_stream_with_stacked_producer_and_onerror_handler(): void
    {
        $stream = Stream::from(['a b c', 'd e'])->onError(OnError::abort())->tokenize();
        
        self::assertSame(['a', 'b', 'c', 'd', 'e'], \iterator_to_array($stream));
    }
    
    public function test_iterate_stream_with_onerror_handler_and_failed_assertion(): void
    {
        $this->expectExceptionObject(AssertionFailed::exception(null, 2, Check::VALUE));
        
        \iterator_to_array(Stream::from(['a', 'b', null, 'c', 'd'])->onError(OnError::skip())->assert('is_string'));
    }
    
    /**
     * @dataProvider getDataForTestIterateOverZeroArgs
     */
    #[DataProvider('getDataForTestIterateOverZeroArgs')]
    public function test_iterateOver_zero_args(FullMemo $num, callable $producer): void
    {
        $actual = Stream::from([2, 0, 1, 3, 1, 2])
            ->remember($num)
            ->iterate($producer)
            ->without(['e', 'y', 'u', 'i', 'o', 'a'])
            ->chunkBy($num->key())
            ->concat('')
            ->toString();
        
        self::assertSame('hp,chj,kqn,bzvr,kqn,hp', $actual);
    }
    
    public static function getDataForTestIterateOverZeroArgs(): iterable
    {
        $dataSet = [
            ['c', 'e', 'h', 'j'],
            ['k', 'q', 'n'],
            ['h', 'p'],
            ['b', 'z', 'o', 'v', 'r'],
        ];
        
        $num = Memo::full();
        
        yield 'array' => [$num, static fn(): array => $dataSet[$num->value()->read()]];

        yield 'iterable' => [$num, static fn(): iterable => $dataSet[$num->value()->read()]];

        yield 'Generator' => [$num, static function () use ($dataSet, $num): \Generator {
            yield from $dataSet[$num->value()->read()];
        }];

        yield 'Iterator' => [$num, static function () use ($dataSet, $num): \Iterator {
            return new \ArrayIterator($dataSet[$num->value()->read()]);
        }];
        
        yield 'Traversable' => [$num, static function () use ($dataSet, $num): \Traversable {
            return new \ArrayIterator($dataSet[$num->value()->read()]);
        }];
    }
    
    public function test_iterateOver_zero_args_with_onerror_handler(): void
    {
        $dataSet = [
            ['c', 'e', 'h', 'j'],
            ['k', 'q', 'n'],
            ['h', 'p'],
            ['b', 'z', 'o', 'v', 'r'],
        ];
        
        $num = Memo::full();
        
        $actual = Stream::from([2, 0, 1, 3, 1, 2])
            ->onError(OnError::abort())
            ->remember($num)
            ->iterate(static fn(): array => $dataSet[$num->value()->read()])
            ->without(['e', 'y', 'u', 'i', 'o', 'a'])
            ->chunkBy($num->key())
            ->concat('')
            ->toString();
        
        self::assertSame('hp,chj,kqn,bzvr,kqn,hp', $actual);
    }
    
    public function test_iterateOver_one_arg(): void
    {
        $dataSet = [
            ['c', 'e', 'h', 'j'],
            ['k', 'q', 'n'],
            ['h', 'p'],
            ['b', 'z', 'o', 'v', 'r'],
        ];
        
        $actual = Stream::from([2, 0, 1, 3, 1, 2])
            ->putIn($key, Check::KEY)
            ->iterate(static fn(int $num): iterable => $dataSet[$num])
            ->without(['e', 'y', 'u', 'i', 'o', 'a'])
            ->chunkBy(Discriminators::readFrom($key))
            ->concat('')
            ->toString();
        
        self::assertSame('hp,chj,kqn,bzvr,kqn,hp', $actual);
    }
    
    public function test_iterateOver_one_arg_with_onerror_handler(): void
    {
        $dataSet = [
            ['c', 'e', 'h', 'j'],
            ['k', 'q', 'n'],
            ['h', 'p'],
            ['b', 'z', 'o', 'v', 'r'],
        ];
        
        $actual = Stream::from([2, 0, 1, 3, 1, 2])
            ->onError(OnError::abort())
            ->putIn($key, Check::KEY)
            ->iterate(static fn(int $num): array => $dataSet[$num])
            ->without(['e', 'y', 'u', 'i', 'o', 'a'])
            ->chunkBy(Discriminators::readFrom($key))
            ->concat('')
            ->toString();
        
        self::assertSame('hp,chj,kqn,bzvr,kqn,hp', $actual);
    }
    
    public function test_iterateOver_two_args(): void
    {
        $dataSet = [
            ['c', 'e', 'h', 'j'],
            ['k', 'q', 'n'],
            ['h', 'p'],
            ['b', 'z', 'o', 'v', 'r'],
        ];
        
        $actual = Stream::from([2, 0, 1, 3, 1, 2])
            ->iterate(static function (int $num, int $key) use ($dataSet): \Generator {
                foreach ($dataSet[$num] as $value) {
                    yield $key => $value;
                }
            })
            ->without(['e', 'y', 'u', 'i', 'o', 'a'])
            ->chunkByKey()
            ->concat('')
            ->toString();
        
        self::assertSame('hp,chj,kqn,bzvr,kqn,hp', $actual);
    }
    
    public function test_iterateOver_two_args_with_onerror_handler(): void
    {
        $dataSet = [
            ['c', 'e', 'h', 'j'],
            ['k', 'q', 'n'],
            ['h', 'p'],
            ['b', 'z', 'o', 'v', 'r'],
        ];
        
        $actual = Stream::from([2, 0, 1, 3, 1, 2])
            ->onError(OnError::abort())
            ->iterate(static function (int $num, int $key) use ($dataSet): \Generator {
                foreach ($dataSet[$num] as $value) {
                    yield $key => $value;
                }
            })
            ->without(['e', 'y', 'u', 'i', 'o', 'a'])
            ->chunkByKey()
            ->concat('')
            ->toString();
        
        self::assertSame('hp,chj,kqn,bzvr,kqn,hp', $actual);
    }
    
    public function test_use_flatMap_instead_of_iterateOver(): void
    {
        $dataSet = [
            ['c', 'e', 'h', 'j'],
            ['k', 'q', 'n'],
            ['h', 'p'],
            ['b', 'z', 'o', 'v', 'r'],
        ];
        
        $actual = Stream::from([2, 0, 1, 3, 1, 2])
            ->putIn($key, Check::KEY)
            ->flatMap(static fn(int $num): array => $dataSet[$num], 1)
            ->without(['e', 'y', 'u', 'i', 'o', 'a'])
            ->chunkBy(Discriminators::readFrom($key))
            ->concat('')
            ->toString();
        
        self::assertSame('hp,chj,kqn,bzvr,kqn,hp', $actual);
    }
    
    public function test_iterateOver_throws_exception_when_callable_requires_invalid_number_of_args(): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::invalidIterateOverCallback(3));
        
        Stream::empty()->iterate(static fn($a, $b, $c): iterable => []);
    }
    
    
    public function test_iterateOver_throws_exception_when_declared_type_of_callable_is_invalid(): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::wrongTypeOfIterateOverCallback());
        
        Stream::empty()->iterate(static fn(): int => 5);
    }
    
    public function test_prototype_creates_separate_instances_of_stream(): void
    {
        $s1 = Stream::prototype()->filter(Filters::isString()->or(Filters::isInt()));
        
        $s2 = $s1->join(['a', 'b', false, 'c', 'd', 'e']);
        $s3 = $s2->only(['e', 'y', 'u', 'i', 'o', 'a']);
        $s4 = $s3->map('strtoupper');
        
        $s5 = $s1->join([1, 2, false, 3, 4, 5]);
        $s6 = $s5->filter(Filters::number()->isOdd());
        $s7 = $s5->filter(Filters::number()->isEven());
        $s8 = $s7->reduce(Reducers::sum());
        
        $s9 = $s6->rsort()->limit(2);
        
        self::assertTrue($s1->isEmpty()->get());
        self::assertSame('abcde', $s2->toString(''));
        self::assertSame('ae', $s3->toString(''));
        self::assertSame('AE', $s4->toString(''));
        self::assertSame('12345', $s5->toString(''));
        self::assertSame('135', $s6->toString(''));
        self::assertSame('24', $s7->toString(''));
        self::assertSame('6', $s8->toString(''));
        self::assertSame('53', $s9->toString(''));
        
        self::assertSame([2, 4], $s7->toArray());
        self::assertSame(3, $s6->skip(1)->first()->get());
        self::assertSame('["E","A"]', $s4->reverse()->toJson());
        self::assertSame(3, $s1->wrap([1, true, 2, 3])->count()->get());
        
        $s10 = $s6->omitReps()->collectValues();
        $s10->consume([2, 5, 9, 3, 8, 7, 1, 4]);
        self::assertSame([5, 9, 3, 7, 1, 3, 5], $s10->get());
        
        $s11 = $s2->find('c');
        self::assertTrue($s11->found());
        self::assertSame([3, 'c'], $s11->tuple());
        
        self::assertSame(4, $s9->reduce(Reducers::average())->get());
    }
    
    public function test_prototype_with_group(): void
    {
        $stream = Stream::prototype(['the quick brown fox', 'jumps over the lazy dog'])
            ->flatMap(static fn(string $line): array => \explode(' ', $line), 1)
            ->filter(Filters::length()->between(4, 5))
            ->countIn($countWords)
            ->mapKey('strlen');
        
        //first iteration
        $group1 = $stream->group();
        
        self::assertSame(5, $countWords);
        self::assertSame([5, 4], $group1->classifiers());
        self::assertSame(['over', 'lazy'], $group1->get(4)->get());
        
        //second iteration
        $group2 = $stream->group();
        
        self::assertSame(10, $countWords);
        self::assertSame([5, 4], $group2->classifiers());
        self::assertSame(['quick', 'brown', 'jumps'], $group2->get(5)->get());
    }
    
    public function test_prototype_with_feed(): void
    {
        $sumA = Stream::empty()->filterKey('a')->reduce(Reducers::sum());
        
        $stream = Stream::prototype()
            ->join(['b:5', 'c:3', 'a:8', 'c:2', 'a:1', 'b:2', 'c:2', 'a:4', 'c:5'])
            ->split(':')
            ->unpackTuple()
            ->castToInt()
            ->feed($sumA)
            ->limit(7);
        
        //first iteration
        self::assertSame([
            'b' => [5, 2],
            'c' => [3, 2, 2],
            'a' => [8, 1],
        ], $stream->categorizeByKey()->toArrayAssoc());
        
        self::assertSame(9, $sumA->get());
        
        //second iteration
        self::assertSame([3, 2, 1, 2, 2], $stream->lessThan(5)->toArray());
        
        self::assertSame(18, $sumA->get());
        
        //third iteration
        self::assertSame([5, 3, 8, 2, 1, 2, 2], $stream->toArray());
        
        self::assertSame(27, $sumA->get());
    }
    
    public function test_prototype_with_loop_and_call(): void
    {
        $collatz = Memo::sequence();
        $counter = 0;
        
        $stream = Stream::prototype([3])
            ->call($collatz)
            ->while(Filters::greaterThan(1))
            ->mapWhen(
                static fn(int $n): bool => ($n & 1) === 0,
                static fn (int $n): int => $n >> 1,
                static fn (int $n): int => (3 * $n + 1),
            );
        
        $withCounter = $stream->countIn($counter);
        $withFilter = $stream->filter(Filters::number()->isOdd()->or(Filters::number()->isEven()));

        $expected = [3, 10, 5, 16, 8, 4, 2, 1];
        
        //first iteration
        $stream->loop()->run();
        
        self::assertSame(0, $counter);
        self::assertSame(\count($expected), $collatz->count());
        self::assertSame($expected, $collatz->getValues());
        self::assertSame(\array_sum($expected), $collatz->reduce(Reducers::sum()));
        
        //second iteration
        $withCounter->loop(true);
        
        self::assertSame(7, $counter);
        self::assertSame(2 * \count($expected), $collatz->count());
        self::assertSame(2 * \array_sum($expected), $collatz->reduce(Reducers::sum()));
        
        //third iteration
        $counter = 0;
        $withFilter->loop()->run();
        
        self::assertSame(0, $counter);
        self::assertSame(3 * \count($expected), $collatz->count());
        self::assertSame(3 * \array_sum($expected), $collatz->reduce(Reducers::sum()));
    }
    
    public function test_prototype_with_call_counter(): void
    {
        $counter = Consumers::counter();
        $stream = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e', 5])->call($counter);
        
        //first iteration
        self::assertSame('abcde', $stream->onlyStrings()->toString(''));
        
        self::assertSame(10, $counter->get());
        
        //second iteration
        self::assertSame('12345', $stream->onlyIntegers()->toString(''));
        
        self::assertSame(20, $counter->get());
    }
    
    public function test_prototype_with_callOnce_and_callMax(): void
    {
        $once = Consumers::counter();
        $twice = Consumers::counter();
        
        $stream = Stream::prototype()
            ->join(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e', 5])
            ->callOnce($once)
            ->callMax(2, $twice);
        
        //first iteration
        self::assertSame('abcde', $stream->onlyStrings()->toString(''));
        
        self::assertSame(1, $once->get());
        self::assertSame(2, $twice->get());
        
        //second iteration
        self::assertSame(15, $stream->onlyIntegers()->reduce(Reducers::sum())->get());
        
        self::assertSame(2, $once->get());
        self::assertSame(4, $twice->get());

        $other = $stream->chunk(2, true)->unpackTuple();
    
        //third iteration
        self::assertSame('abcde', $other->collectKeys()->toString(''));
        
        self::assertSame(3, $once->get());
        self::assertSame(6, $twice->get());
    
        //fourth iteration
        self::assertSame([1, 2, 3, 4, 5], $other->toArray());
        
        self::assertSame(4, $once->get());
        self::assertSame(8, $twice->get());
    }
    
    public function test_prototype_with_route(): void
    {
        $sumInts = Reducers::sum();
        $strings = Collectors::values();
        
        $stream = Stream::prototype()
            ->route('is_string', $strings)
            ->route('is_int', $sumInts)
            ->join(['a', 1, 15.0, 'b', 2, 'c', false, 3, 'd', 25.0]);
        
        //first iteration
        self::assertSame(3, $stream->count()->get());
        
        self::assertSame(6, $sumInts->result());
        self::assertSame('abcd', $strings->toString(''));
        
        //second iteration
        self::assertSame([15.0, 25.0], $stream->filter(Filters::isFloat())->toArray());
        
        self::assertSame(12, $sumInts->result());
        self::assertSame('abcdabcd', $strings->toString(''));
        
        //third iteration
        self::assertSame([2 => 15.0, 6 => false, 9 => 25.0], $stream->toArray(true));
        
        self::assertSame(18, $sumInts->result());
        self::assertSame('abcdabcdabcd', $strings->toString(''));
    }
    
    public function test_prototype_with_switch(): void
    {
        $sumInts = Reducers::sum();
        $strings = Collectors::values();
        
        $discriminator = static function ($v): string {
            switch (true) {
                case \is_string($v): return 'str';
                case \is_int($v): return 'int';
                default: return 'other';
            }
        };
        
        $stream = Stream::prototype()
            ->limit(10)
            ->switch($discriminator, [
                'str' => $strings,
                'int' => $sumInts,
            ])
            ->join(['a', 1, 15.0, 'b', 2, 'c', false, 3, 'd', 25.0, 'e']);
        
        //first iteration
        self::assertSame(3, $stream->count()->get());
        
        self::assertSame(6, $sumInts->result());
        self::assertSame('abcd', $strings->toString(''));
        
        //second iteration
        self::assertSame([15.0, 25.0], $stream->filter(Filters::isFloat())->toArray());
        
        self::assertSame(12, $sumInts->result());
        self::assertSame('abcdabcd', $strings->toString(''));
        
        //third iteration
        self::assertSame([2 => 15.0, 6 => false, 9 => 25.0], $stream->toArray(true));
        
        self::assertSame(18, $sumInts->result());
        self::assertSame('abcdabcdabcd', $strings->toString(''));
    }
    
    public function test_prototype_with_dispatch(): void
    {
        $sumInts = Reducers::sum();
        $strings = Collectors::values();
        
        $discriminator = static function ($v): string {
            switch (true) {
                case \is_string($v): return 'str';
                case \is_int($v): return 'int';
                default: return 'other';
            }
        };
        
        $stream = Stream::prototype(['a', 1, 15.0, 'b', 2, 'c', false, 3, 'd', 25.0, 'e'])
            ->dispatch($discriminator, [
                'str' => $strings,
                'int' => $sumInts,
                'other' => Consumers::idle(),
            ])
            ->limit(5);
        
        //first iteration
        self::assertSame(5, $stream->count()->get());
        
        self::assertSame(3, $sumInts->result());
        self::assertSame('ab', $strings->toString(''));
        
        //second iteration
        self::assertSame([15.0], $stream->filter('is_float')->toArray());
        
        self::assertSame(6, $sumInts->result());
        self::assertSame('abab', $strings->toString(''));
        
        //third iteration
        self::assertSame(['a', 1, 15.0, 'b', 2], $stream->toArray(true));
        
        self::assertSame(9, $sumInts->result());
        self::assertSame('ababab', $strings->toString(''));
    }
    
    public function test_prototype_with_fork(): void
    {
        $counter = Consumers::counter();
        
        $stream = Stream::prototype()
            ->callOnce($counter)
            ->onlyIntegers()
            ->fork(Discriminators::evenOdd(), Stream::empty()->greaterOrEqual(5)->lessOrEqual(10)->collectValues())
            ->join([5, 2, 3, 'a', 4, 1, 6, 'b', 15, 2, 9, 'c', 3, 6, 4, 'd', 12]);
        
        //first iteration
        self::assertSame([
            'odd' => [5, 9],
            'even' => [6, 6],
        ], $stream->toArrayAssoc());
        
        self::assertSame(1, $counter->get());
        
        //second iteration
        self::assertSame(2, $stream->count()->get());
        
        self::assertSame(2, $counter->get());
        
        //third iteration
        self::assertSame('{"odd":[5,9],"even":[6,6]}', $stream->toJsonAssoc());
        
        self::assertSame(3, $counter->get());
        
        //fourth iteration
        self::assertSame(['odd' => 14, 'even' => 12], $stream->map(Reducers::sum())->toArrayAssoc());
        
        self::assertSame(4, $counter->get());
    }
    
    public function test_prototype_with_forkMatch(): void
    {
        $counter = Consumers::counter();
        
        $stream = Stream::prototype()
            ->callOnce($counter)
            ->forkMatch(Discriminators::yesNo('is_string', 'str', 'int'), [
                'str' => Stream::empty()->map('strtoupper')->reduce(Reducers::concat()),
                'int' => Stream::empty()
                    ->filter(Filters::number()->between(5, 10))
                    ->classify(Discriminators::evenOdd())
                    ->forkByKey(Reducers::sum())
                    ->collect()
            ])
            ->flat(1)
            ->join([5, 2, 3, 'a', 4, 1, 6, 'b', 15, 2, 9, 'c', 3, 6, 4, 'd', 12]);
        
        //first iteration
        self::assertSame([
            'str' => 'ABCD',
            'odd' => 14,
            'even' => 12,
        ], $stream->toArrayAssoc());
        
        self::assertSame(1, $counter->get());
        
        //second iteration
        self::assertSame(3, $stream->count()->get());

        self::assertSame(2, $counter->get());

        //third iteration
        self::assertSame('{"str":"ABCD","odd":14,"even":12}', $stream->toJsonAssoc());

        self::assertSame(3, $counter->get());

        //fourth iteration
        self::assertSame(['even', 'odd', 'str'], $stream->collectKeys()->transform('sort')->get());

        self::assertSame(4, $counter->get());
    }
    
    public function test_prototype_with_forkMatch_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream = Stream::prototype()
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->forkMatch(Discriminators::yesNo('is_string', 'str', 'int'), [
                'str' => Stream::empty()->map('strtoupper')->reduce(Reducers::concat()),
                'int' => Stream::empty()
                    ->filter(Filters::number()->between(5, 10))
                    ->classify(Discriminators::evenOdd())
                    ->forkByKey(Reducers::sum())
                    ->collect()
            ])
            ->flat(1)
            ->join([5, 2, 3, 'a', 4, 1, 6, 'b', 15, 2, 9, 'c', 3, 6, 4, 'd', 12]);
        
        //first iteration
        self::assertSame([
            'str' => 'ABCD',
            'odd' => 14,
            'even' => 12,
        ], $stream->toArrayAssoc());
        
        self::assertSame(1, $counter->get());
        
        //second iteration
        self::assertSame(3, $stream->count()->get());

        self::assertSame(2, $counter->get());

        //third iteration
        self::assertSame('{"str":"ABCD","odd":14,"even":12}', $stream->toJsonAssoc());

        self::assertSame(3, $counter->get());

        //fourth iteration
        self::assertSame(['even', 'odd', 'str'], $stream->collectKeys()->transform('sort')->get());

        self::assertSame(4, $counter->get());
    }
}