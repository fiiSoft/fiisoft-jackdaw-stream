<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\IdleForkHandler;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
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
}