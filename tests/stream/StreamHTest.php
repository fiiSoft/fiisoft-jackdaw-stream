<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Exception\PipeExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\IdleForkHandler;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Special\Assert\AssertionFailed;
use FiiSoft\Jackdaw\Producer\Producers;
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
        $this->performTest050(false);
    }
    
    public function test_two_slice_mappers_with_other_mappers_between_them_with_onerror_handler(): void
    {
        $this->performTest050(true);
    }
    
    private function performTest050(bool $onError): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->mapKey(Mappers::value())
            ->chunk(4)
            ->map(Mappers::limit(3))
            ->map(Mappers::reindexKeys())
            ->map(Mappers::forEach('\strtoupper'))
            ->map(Mappers::skip(1));
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([
            [1 => 'B', 'C'],
            [1 => 'F', 'G'],
            [1 => 'J', 'K'],
        ], $stream->toArray());
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
        $this->performTest051(false);
    }
    
    public function test_stream_skipNth_3_with_onerror_handler(): void
    {
        $this->performTest051(true);
    }
    
    private function performTest051(bool $onError): void
    {
        $stream = Stream::from([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])->skipNth(3);
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame([1, 2, 4, 5, 7, 8, 10, 11, 13, 14], $stream->toArray());
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
        $this->performTest052(false);
    }
    
    public function test_forkMatch_simple_with_onerror_handler(): void
    {
        $this->performTest052(true);
    }
    
    private function performTest052(bool $onError): void
    {
        $stream = Stream::from([6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9])
            ->forkMatch(
                Discriminators::evenOdd(),
                [
                    'even' => Reducers::min(),
                    'odd' => Reducers::max(),
                ]
            );
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([
            'even' => 2,
            'odd' => 9,
        ], $stream->toArrayAssoc());
    }
    
    public function test_forkMatch_sophisticated(): void
    {
        $this->performTest053(false);
    }
    
    public function test_forkMatch_sophisticated_with_onerror_handler(): void
    {
        $this->performTest053(true);
    }
    
    private function performTest053(bool $onError): void
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
        
        $stream = Stream::from([4, 'a', 3, null, 15.6, 'b', 'c', 9.24, false, 7, 'd', 6, true, 2.5, 'e', 5])
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
            );
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([
            'even' => 4,
            'odd' => 7,
            'string' => 'A|B|C|D|E',
            'float' => [15.6, 9.24, 2.5],
            'bool' => [8 => false, 12 => true],
        ], $stream->toArrayAssoc());
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
        $this->performTest054(false);
    }
    
    public function test_iterating_of_collectKey_with_onerror_handler(): void
    {
        $this->performTest054(true);
    }
    
    private function performTest054(bool $onError): void
    {
        $keys = ['a', 'b', 'c', 'd', 'e', 'f'];
        
        $stream = Stream::from(\array_flip($keys));
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream = $stream->collectKeys();
        
        self::assertSame($keys, \iterator_to_array($stream));
        self::assertSame($keys, $stream->toArrayAssoc());
        self::assertSame($keys, $stream->toArray());
        self::assertSame($keys, $stream->get());
        
        self::assertCount(\count($keys), $stream);
        
        $stream->destroy();
    }
    
    public function test_iterating_of_collectValues_returns_values_without_keys(): void
    {
        $this->performTest055(false);
    }
    
    public function test_iterating_of_collectValues_with_onerror_handler(): void
    {
        $this->performTest055(true);
    }
    
    private function performTest055(bool $onError): void
    {
        $data = [5 => 'a', 2 => 'b', 4 => 'c', 1 => 'd', 3 => 'e', 6 => 'f'];
        $values = \array_values($data);
        
        $stream = Stream::from($data);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream = $stream->collectValues();
        
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
    
    public function test_limit_0_should_execute_stream(): void
    {
        $actual = Stream::from([1, 2, 3, 4, 5])->countIn($counter)->limit(0)->toArray();
        
        self::assertEmpty($actual);
        self::assertSame(1, $counter);
    }
    
    public function test_limit_0_should_execute_stream_with_onerror_handler(): void
    {
        $actual = Stream::from([1, 2, 3, 4, 5])->onError(OnError::abort())->countIn($counter)->limit(0)->toArray();
        
        self::assertEmpty($actual);
        self::assertSame(1, $counter);
    }
    
    public function test_forkMatch_with_Stream_as_fallback_handler(): void
    {
        $evenOdd = Discriminators::evenOdd();
        
        $discriminator = static function ($value) use ($evenOdd): ?string {
            if (\is_int($value)) {
                return $evenOdd->classify($value);
            }
            
            if (\is_string($value)) {
                return \ctype_lower($value) ? 'low' : 'big';
            }
            
            return null;
        };
        
        $handlers = [
            'even' => Stream::empty()->sort()->collectValues(),
            'odd' => Stream::empty()->sort()->collectValues(),
        ];
        
        $producer = ['N', 5, 'a', 8, 'h', 'e', false, 'D', 4, 'O', 1, 'q'];
        $prototype = Stream::empty()->onlyStrings()->map('strtolower')->sort()->reduce(Reducers::concat(','));
        $expected = ['even' => [4, 8], 'odd' => [1, 5], 'big' => 'd,n,o', 'low' => 'a,e,h,q'];
        
        self::assertSame(
            $expected,
            Stream::from($producer)
                ->forkMatch($discriminator, $handlers, $prototype)
                ->toArrayAssoc()
        );
        
        self::assertSame(
            $expected,
            Stream::from($producer)
                ->onError(OnError::abort())
                ->forkMatch($discriminator, $handlers, $prototype)
                ->toArrayAssoc()
        );
    }
    
    public function test_stream_with_transformation_set_on_LastOperation(): void
    {
        $producer = ['a', 'b', 1, 'c', 'd', 2, 'e'];
        
        $stream1 = Stream::from($producer)->onlyStrings()->count();
        self::assertSame(5, $stream1->get());
        
        $stream2 = Stream::from($producer)->onlyStrings()->reduce(Reducers::concat());
        
        $stream3 = $stream2->wrap(['f', 3, 'g', 4, 'h']);
        self::assertNotSame($stream2, $stream3);
        self::assertSame('fgh', $stream3->get());
        self::assertSame('abcde', $stream2->get());
        self::assertSame(5, $stream1->get());
        
        self::assertNotSame($stream1, $stream2);
        self::assertSame('abcde', $stream2->get());
        self::assertSame(5, $stream1->get());
        
        $stream4 = Stream::from($producer)->onlyStrings()->join([5, 'f', 6, 'g'])->reverse();
        self::assertNotSame($stream1, $stream4);
        self::assertSame('gfedcba', $stream4->toString(''));
        self::assertSame('fgh', $stream3->get());
        self::assertSame('abcde', $stream2->get());
        self::assertSame(5, $stream1->get());
        
        $stream5 = $stream2->transform('strtoupper');
        self::assertSame($stream2, $stream5);
        self::assertSame('ABCDE', $stream5->get());
        self::assertSame('fgh', $stream3->get());
        self::assertSame('ABCDE', $stream2->get());
        self::assertSame(5, $stream1->get());
    }
    
    public function test_stream_with_cache_works_like_always(): void
    {
        $stream = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4])
            ->onlyIntegers()
            ->countIn($counterBeforeCache)
            ->cache()
            ->countIn($counterAfterCache)
            ->count();
        
        self::assertSame(4, $stream->get());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(4, $counterAfterCache);
    }
    
    public function test_iterate_array_with_callback_filter_and_get_assoc_array(): void
    {
        self::assertSame(
            [1 => 1, 3 => 2, 5 => 3],
            Stream::from(['a', 1, 'b', 2, 'c', 3])->filter('is_int')->toArrayAssoc()
        );
    }
    
    public function test_iterate_array_and_filter_only_numbers_and_get_assoc_json(): void
    {
        self::assertSame(
            '{"0":"a","1":1,"2":"b","3":2,"4":"c","6":"d"}',
            Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4])->filterWhen('is_int', Filters::lessThan(3))->toJsonAssoc()
        );
    }
    
    public function test_iterate_producer_and_agregate_keys_into_array(): void
    {
        $producer = Producers::combinedFrom(
            ['a', 'c', 'b', 'a', 'b', 'b', 'c', 'a', 'a', 'c', 'a'],
            [3,    2,   1,   5,   3,   7,   6,   0,   4,   5,   2]
        );
        
        self::assertSame([
            ['a' => 3, 'b' => 1],
            ['a' => 5, 'b' => 3],
            ['b' => 7, 'a' => 0],
        ], Stream::from($producer)->aggregate(['a', 'b'])->toArray(true));
    }
    
    public function test_iterate_sequential_int_producer_and_assert_every_number_is_less_than_5(): void
    {
        $this->expectExceptionObject(AssertionFailed::exception(5, 4, Check::VALUE));
        
        Producers::sequentialInt()->stream()->assert(Filters::lessThan(5))->run();
    }
    
    public function test_classify_even_odd_numbers_and_group_in_array(): void
    {
        self::assertSame([
            'odd' => [5, 3, 1],
            'even' => [2, 4],
        ], Stream::from([5, 2, 3, 4, 1])->classify(Discriminators::evenOdd())->group()->toArray());
    }
    
    public function test_collect_data_in_collector(): void
    {
        $all = Collectors::default();
        $numbers = Collectors::default(false);
        
        Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4])
            ->collectIn($all)
            ->onlyIntegers()
            ->collectIn($numbers)
            ->run();
        
        self::assertSame(['a', 1, 'b', 2, 'c', 3, 'd', 4], $all->toArray());
        self::assertSame([1, 2, 3, 4], $numbers->toArray());
    }
    
    public function test_collectKeysIn(): void
    {
        $keys = [];
        
        Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->collectKeysIn(Collectors::array($keys))->run();
        
        self::assertSame(['a', 'b', 'c'], $keys);
    }
    
    public function test_countIn_with_filter(): void
    {
        Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4])->onlyIntegers()->countIn($countInts)->run();
        
        self::assertSame(4, $countInts);
    }
    
    public function test_everyNth_with_filter(): void
    {
        self::assertSame('a,d', Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4])->onlyStrings()->everyNth(3)->toString());
    }
    
    public function test_filterMany_unconditional(): void
    {
        $actual = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e', 5])
            ->onlyIntegers()
            ->greaterThan(1)
            ->lessThan(5)
            ->toArray();
        
        self::assertSame([2, 3, 4], $actual);
    }
    
    public function test_filterMany_conditional(): void
    {
        $actual = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e', 5])
            ->filterWhen('is_int', Filters::greaterThan(1))
            ->filterWhen('is_int', Filters::lessThan(5))
            ->omit('is_string')
            ->toArray();
        
        self::assertSame([2, 3, 4], $actual);
    }
    
    public function test_filterWhile(): void
    {
        self::assertSame(
            [1, 0, 1, -1],
            Stream::from([-3, -2, -1, 1, 0, 1, -1])->filterWhile(Filters::lessThan(1), false)->toArray()
        );
    }
    
    public function test_flip_with_filter(): void
    {
        $actual = Stream::from(['a', 'b', 'c', 'd'])
            ->filter(Filters::greaterThan(0), Check::KEY)
            ->flip()
            ->filter(Filters::lessThan(3))
            ->flip()
            ->toArrayAssoc();
        
        self::assertSame([1 => 'b', 2 => 'c'], $actual);
    }
    
    public function test_stream_forkMatch(): void
    {
        $this->performTest099(false);
    }

    public function test_stream_forkMatch_with_onerror_handler(): void
    {
        $this->performTest099(true);
    }
    
    private function performTest099(bool $onError): void
    {
        $stream = Stream::from([4, 'a', 3, 15.6, 'b', 'c', 9.24, 7, 'd', 6, 2.5, 'e', 5])
            ->forkMatch($this->forkMatchDiscriminator(), $this->forkMatchHandlers());
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame($this->expectedForkMatchStreamResult(), $stream->toArrayAssoc());
    }
    
    public function test_stream_collect_count_toArray_transform_toJson(): void
    {
        $this->performTest100(false);
    }
    
    public function test_stream_collect_count_toArray_transform_toJson_with_onerror_handler(): void
    {
        $this->performTest100(true);
    }
    
    private function performTest100(bool $onError): void
    {
        $stream = Stream::from([4, 'a', 3, 15.6, 'b', 'c', 9.24, 7, 'd', 6, 2.5, 'e', 5])
            ->forkMatch($this->forkMatchDiscriminator(), $this->forkMatchHandlers());
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream = $stream->collect();
        
        self::assertSame(4, $stream->count());
        self::assertSame($this->expectedForkMatchStreamResult(), $stream->toArrayAssoc());
        
        $stream->transform(Mappers::forEach(static fn($v) => \is_array($v) ? \array_sum($v) : $v));
        
        self::assertSame([
            'even' => 4,
            'odd' => 7,
            'string' => 'A|B|C|D|E',
            'float' => \array_sum([15.6, 9.24, 2.5]),
        ], $stream->get());
        
        self::assertSame('{"even":4,"odd":7,"string":"A|B|C|D|E","float":27.34}', $stream->toJsonAssoc());
    }
    
    private function forkMatchDiscriminator(): callable
    {
        return static function ($value): string {
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
    }
    
    private function forkMatchHandlers(): array
    {
        return [
            'even' => Reducers::min(),
            'odd' => Reducers::max(),
            'string' => Stream::empty()->map('strtoupper')->reduce(Reducers::concat('|')),
            'float' => Collectors::values(),
        ];
    }
    
    private function expectedForkMatchStreamResult(): array
    {
        return [
            'even' => 4,
            'odd' => 7,
            'string' => 'A|B|C|D|E',
            'float' => [15.6, 9.24, 2.5],
        ];
    }
    
    public function test_cannot_add_operation_to_stream_case_1(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::cannotAddOperationToStartedStream());
        
        //Arrange
        $stream = Stream::from([1, 2, 3])->limit(2);
        $stream->run();
        
        //Act
        $stream->filter('is_int');
    }
    
    public function test_cannot_add_operation_to_stream_case_2(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::cannotAddOperationToStartedStream());
        
        //Arrange
        $stream = Stream::from([1, 2, 3]);
        $stream->toArray();
        
        //Act
        $stream->filter('is_int');
    }
    
    public function test_cannot_add_operation_to_stream_case_3(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::cannotExecuteStreamMoreThanOnce());
        
        //Arrange
        $stream = Stream::from([1, 2, 3])->reduce(Reducers::sum());
        $stream->get();
        
        //Act
        $stream->consume([5, 2, 3]);
    }
    
    public function test_cannot_add_operation_to_stream_case_4(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::cannotAddOperationToStartedStream());
        
        //Arrange
        $stream = Stream::from([1, 2, 'a', 3])->onlyIntegers();
        $stream->collectValues()->get();
        
        //Act
        $stream->greaterThan(1);
    }
    
    public function test_cannot_add_operation_to_stream_case_5(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::cannotAddOperationToStartedStream());
        
        //Arrange
        $stream = Stream::from([1, 2, 'a', 3])->onlyIntegers();
        $stream->collectValues()->get();
        
        //Act
        $stream->reduce(Reducers::sum());
    }
    
    public function test_cannot_add_operation_to_stream_case_6(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::cannotAddOperationToStartedStream());
        
        //Arrange
        $stream = Stream::from([1, 2, 'a', 3])->onlyIntegers();
        $stream->collectValues()->get();
        
        //Act
        $stream->collect();
    }
    
    public function test_cannot_add_operation_to_stream_case_7(): void
    {
        //Assert
        $this->expectExceptionObject(PipeExceptionFactory::cannotAddOperationToTheFinalOne());
        
        //Arrange
        $stream = Stream::from([1, 2, 'a', 3])->onlyIntegers();
        $stream->collectValues();
        
        //Act
        $stream->collect();
    }
    
    public function test_cannot_add_operation_to_stream_case_10(): void
    {
        //Assert
        $this->expectExceptionObject(PipeExceptionFactory::cannotAddOperationToTheFinalOne());
        
        //Arrange
        $stream = Stream::from([1, 2, 'a', 3])->onlyIntegers();
        $stream->collectValues();
        
        //Act
        $buffer = [];
        $stream->storeIn($buffer);
    }
    
    public function test_cannot_add_operation_to_stream_case_11(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::cannotAddOperationToStartedStream());
        
        //Arrange
        $stream = Stream::from([1, 2, 'a', 3])->onlyIntegers();
        $stream->toArray();
        
        //Act
        $buffer = [];
        $stream->storeIn($buffer);
    }
}