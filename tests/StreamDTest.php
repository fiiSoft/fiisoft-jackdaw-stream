<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Operation\Special\Assert\AssertionFailed;
use FiiSoft\Jackdaw\Producer\Internal\CircularBufferIterator;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseArrayIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseNumericalArrayIterator;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StreamDTest extends TestCase
{
    public function test_complete(): void
    {
        $rowset = [
            ['id' => 3, 'name' => 'Ole'],
            ['id' => 7, 'name' => null],
            ['id' => 2],
            'foo',
        ];
        
        $result = Stream::from($rowset)->complete('name', 'anonymous')->toArray();
        
        $expected = [
            ['id' => 3, 'name' => 'Ole'],
            ['id' => 7, 'name' => 'anonymous'],
            ['id' => 2, 'name' => 'anonymous'],
            [3 => 'foo', 'name' => 'anonymous'],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_moveTo_creates_array(): void
    {
        self::assertSame('[{"num":1},{"num":2}]', Stream::from([1, 2])->moveTo('num')->toJson());
    }
    
    public function test_moveTo_can_move_key_as_well(): void
    {
        self::assertSame([
            'a' => ['chr' => 'a', 'num' => 1],
            'b' => ['chr' => 'b', 'num' => 2],
        ], Stream::from(['a' => 1, 'b' => 2])->moveTo('num', 'chr')->toArrayAssoc());
    }
    
    public function test_tail_short(): void
    {
        self::assertSame([4, 5], Stream::from([1, 2, 3, 4, 5])->tail(2)->toArray());
    }
    
    public function test_tail_long(): void
    {
        self::assertSame([1, 2, 3], Stream::from([1, 2, 3])->tail(6)->toArray());
    }
    
    public function test_limit_after_limit(): void
    {
        self::assertSame([1, 2], Stream::from([1, 2, 3, 4, 5])->limit(4)->limit(2)->toArray());
    }
    
    public function test_skip_after_skip(): void
    {
        self::assertSame([4, 5], Stream::from([1, 2, 3, 4, 5])->skip(1)->skip(2)->toArray());
    }
    
    public function test_reverse_after_reverse(): void
    {
        self::assertSame([1, 2], Stream::from([1, 2])->reverse()->reverse()->toArray());
    }
    
    public function test_reindex_after_reindex(): void
    {
        self::assertSame([1, 2], Stream::from(['a' => 1, 'b' => 2])->reindex()->reindex()->toArrayAssoc());
    }
    
    public function test_flip_after_flip(): void
    {
        self::assertSame(['a' => 1, 'b' => 2], Stream::from(['a' => 1, 'b' => 2])->flip()->flip()->toArrayAssoc());
    }
    
    public function test_shuffle_after_shuffle(): void
    {
        self::assertSame(3, Stream::from([1, 2, 3])->shuffle()->shuffle()->count()->get());
    }
    
    public function test_tail_after_tail(): void
    {
        self::assertSame([4], Stream::from([1, 2, 3, 4])->tail(3)->tail(1)->toArray());
    }
    
    public function test_flat_after_flat(): void
    {
        $expected = [
            'c' => 1,
            'd' => 2,
            'e' => 3,
            'g' => 4,
            'j' => 5,
            'k' => 6,
            'l' => 7,
            'n' => 8,
            'p' => 9,
            'q' => 10,
            's' => 11,
            't' => 12,
            'u' => 13,
            'w' => 14,
            'y' => 15,
            'z' => 16,
        ];
        
        $data = $this->getDataForFlatTest();
        
        self::assertSame($expected, Stream::from($data)->flat()->toArrayAssoc());
        self::assertSame($expected, Stream::from($data)->flat(1)->flat(1)->flat(1)->toArrayAssoc());
    }
    
    private function getDataForFlatTest(): array
    {
        return [
            [ //first
                'a' => [
                    'b' => [
                        'c' => 1,
                        'd' => 2,
                    ],
                    'e' => 3,
                    'f' => [
                        'g' => 4,
                    ]
                ],
                'h' => [
                    'i' => [
                        'j' => 5,
                        'k' => 6,
                    ],
                ],
            ], [ //second
                'l' => 7,
            ], [ //third
                'm' => [
                    'n' => 8,
                    'o' => [
                        'p' => 9,
                        'q' => 10,
                    ],
                    'r' => [
                        's' => 11,
                    ],
                    't' => 12,
                ],
                'u' => 13,
                'v' => [
                    'w' => 14,
                    'x' => [
                        'y' => 15,
                    ],
                    'z' => 16,
                ],
            ],
        ];
    }
    
    public function test_best(): void
    {
        self::assertSame([1, 2], Stream::from([6, 2, 8, 1, 7, 9, 2, 5, 4])->best(2)->toArray());
    }
    
    public function test_worst(): void
    {
        self::assertSame([9, 8], Stream::from([6, 2, 8, 1, 7, 9, 2, 5, 4])->worst(2)->toArray());
    }
    
    public function test_sortBy_with_limit(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
        
        $actual = Stream::from($rowset)->sortBy('age asc', 'name desc', 'id')->limit(2)->toArray();
        
        $expected = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
        ];
        
        self::assertSame($expected, $actual);
    }
    
    public function test_sort_with_limit(): void
    {
        self::assertSame([1, 2], Stream::from([3, 5, 1, 4, 2])->sort()->limit(2)->toArray());
    }
    
    public function test_best_with_limit(): void
    {
        self::assertSame([1, 2], Stream::from([5, 2, 8, 1, 6, 9, 7, 3])->best(4)->limit(2)->toArray());
    }
    
    public function test_MapFieldWhen(): void
    {
        $result = Stream::from([['key' => 'foo'], ['key' => 3]])
            ->mapFieldWhen('key', 'is_string', 'strtoupper', static fn(int $n): int => $n * 2)
            ->toArray();
        
        self::assertSame([['key' => 'FOO'], ['key' => 6]], $result);
    }
    
    public function test_SortLimited_reversed_with_custom_comparator_to_sort_by_value(): void
    {
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])
            ->worst(3, static fn(string $first, string $second): int => $first <=> $second)
            ->toArray();
        
        self::assertSame(['e', 'd', 'c'], $result);
    }
    
    public function test_SortLimited_with_default_comparator_to_sort_by_key(): void
    {
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])
            ->best(3, By::key())
            ->toArray();
        
        self::assertSame(['d', 'a', 'b'], $result);
    }
    
    public function test_SortLimited_reversed_with_default_comparator_to_sort_by_key(): void
    {
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])
            ->worst(3, By::key())
            ->toArray();
        
        self::assertSame(['e', 'c', 'b'], $result);
    }
    
    public function test_SortLimited_with_custom_comparator_to_sort_by_key(): void
    {
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])
            ->best(3, By::key(static fn(int $first, int $second): int => $first <=> $second))
            ->toArray();
        
        self::assertSame(['d', 'a', 'b'], $result);
    }
    
    public function test_SortLimited_reversed_with_custom_comparator_to_sort_by_key(): void
    {
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])
            ->worst(3, By::key(static fn(int $first, int $second): int => $first <=> $second))
            ->toArray();
        
        self::assertSame(['e', 'c', 'b'], $result);
    }
    
    public function test_SortLimited_with_default_comparator_to_sort_by_value_and_key(): void
    {
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])
            ->best(3, By::assoc())
            ->toArray();
        
        self::assertSame(['a', 'b', 'c'], $result);
    }
    
    public function test_SortLimited_reversed_with_default_comparator_to_sort_by_value_and_key(): void
    {
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])
            ->worst(3, By::assoc())
            ->toArray();
        
        self::assertSame(['e', 'd', 'c'], $result);
    }
    
    public function test_SortLimited_with_custom_comparator_to_sort_by_value_and_key(): void
    {
        $comparator = static fn(string $v1, string $v2, int $k1, int $k2): int => $v1 <=> $v2 ?: $k1 <=> $k2;
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])->best(3, By::assoc($comparator))->toArray();
        
        self::assertSame(['a', 'b', 'c'], $result);
    }
    
    public function test_SortLimited_reversed_with_custom_comparator_to_sort_by_value_and_key(): void
    {
        $comparator = static fn(string $v1, string $v2, int $k1, int $k2): int => $v1 <=> $v2 ?: $k1 <=> $k2;
        $result = Stream::from(['d', 'a', 'b', 'c', 'e'])->worst(3, By::assoc($comparator))->toArray();
        
        self::assertSame(['e', 'd', 'c'], $result);
    }
    
    public function test_Fold_with_callback(): void
    {
        $result = Stream::from([2, 3, 4])
            ->castToFloat()
            ->fold(0.5, static fn(float $result, float $value): float => $result * $value)
            ->get();
        
        self::assertSame(12.0, $result);
    }
    
    public function test_hasOnly(): void
    {
        self::assertFalse(Stream::from([3, 1, 2, 1, 3, 2, 4, 1, 2, 3])->hasOnly([1, 2, 3])->get());
        self::assertTrue(Stream::from([3, 1, 2, 1, 3, 2, 1, 2, 3])->hasOnly([1, 2, 3])->get());
    }
    
    public function test_hasOnly_key(): void
    {
        self::assertFalse(Stream::from([3, 1, 2, 1,])->hasOnly([0, 1, 2], Check::KEY)->get());
        self::assertTrue(Stream::from([3, 1, 2])->hasOnly([0, 1, 2], Check::KEY)->get());
    }
    
    public function test_HasOnly_to_check_value_or_key(): void
    {
        $data = ['a' => 1, 'b' => 'a', 'c' => 1, 'd' => 2];
        
        self::assertFalse(Stream::from($data)->hasOnly(['a', 1], Check::ANY)->get());
        self::assertTrue(Stream::from($data)->hasOnly(['a', 1, 2], Check::ANY)->get());
        self::assertTrue(Stream::from($data)->hasOnly(['a', 1, 'd'], Check::ANY)->get());
    }
    
    public function test_HasOnly_to_check_value_and_key(): void
    {
        $data = ['a' => 1, 'b' => 'a', 'c' => 1, 'd' => 2];
        
        self::assertFalse(Stream::from($data)->hasOnly(['a', 1], Check::BOTH)->get());
        self::assertFalse(Stream::from($data)->hasOnly(['a', 'b', 'c', 'd', 1], Check::BOTH)->get());
        self::assertTrue(Stream::from($data)->hasOnly(['a', 'b', 'c', 'd', 1, 2], Check::BOTH)->get());
    }
    
    public function test_HasEvery_to_check_value_or_key(): void
    {
        self::assertFalse(Stream::from(['a' => 1, 'b' => 'c'])->hasEvery(['b', 2], Check::ANY)->get());
        self::assertTrue(Stream::from(['a' => 1, 'b' => 'c'])->hasEvery(['b', 1], Check::ANY)->get());
    }
    
    public function test_HasEvery_to_check_value_and_key(): void
    {
        self::assertFalse(Stream::from(['a' => 'b', 'b' => 'a'])->hasEvery(['a', 1], Check::BOTH)->get());
        self::assertTrue(Stream::from(['a' => 'b', 'b' => 'a'])->hasEvery(['b', 'a'], Check::BOTH)->get());
    }
    
    public function test_Result_allows_to_transform_stream_result_without_affecting_it(): void
    {
        $result = Stream::from([1, 2, 3, 4])
            ->reduce('array_sum')
            ->transform(static fn(int $sum): int => $sum * 2);
        
        self::assertSame(20, $result->get());
        
        $result->transform(static fn(int $sum) => $sum / 2);
        
        self::assertSame(5, $result->get());
    }
    
    public function test_Result_allows_to_produce_result_by_callable_when_empty_was_stream(): void
    {
        $result = Stream::from([1, 2, 3, 4])
            ->filter(Filters::number()->ge(10))
            ->reduce('array_sum')
            ->transform(static fn(int $sum): int => $sum * 2);
        
        self::assertFalse($result->found());
        self::assertNull($result->get());
        self::assertSame(5, $result->getOrElse(static fn(): int => 5));
    }
    
    public function test_callable_is_not_executed_when_result_is_available(): void
    {
        $result = Stream::from([1, 2, 3, 4])->reduce('array_sum');
        
        self::assertTrue($result->found());
        self::assertSame(10, $result->get());
        self::assertSame(10, $result->getOrElse(static fn(): int => 5));
    }
    
    public function test_Result_allows_to_cast_iterable_object_to_string(): void
    {
        $result = Stream::from([1, 2, 3])
            ->find(2)
            ->transform(static fn(int $n): \ArrayObject => new \ArrayObject(\array_fill(0, $n, 'a')));
        
        self::assertSame('a,a', $result->toString());
    }
    
    public function test_Result_allows_to_transform_iterable_to_array(): void
    {
        $result = Stream::from([1, 2, 3])
            ->find(2)
            ->transform(static fn(int $n): \ArrayObject => new \ArrayObject(\array_fill(0, $n, 'a')));
        
        self::assertSame(['a', 'a'], $result->toArray());
    }
    
    public function test_Tokenize(): void
    {
        $data = ['ala bama', 'okla homa'];
        $expected = ['ala', 'bama', 'okla', 'homa'];
        
        //1
        self::assertSame($expected, Stream::from($data)->tokenize()->toArray());
        self::assertSame($expected, Stream::from($data)->tokenize()->reindex()->toArrayAssoc());
        
        //2
        Stream::from($data)
            ->tokenize()
            ->collectIn($collector = Collectors::default())
            ->run();
        
        self::assertSame($expected, $collector->toArray());
        
        //3
        $tokens = Stream::from($data)->tokenize()->collect(true);
        
        self::assertSame($expected, $tokens->get());
        self::assertSame($expected, $tokens->toArray());
        self::assertSame($expected, $tokens->toArrayAssoc());
        
        //4
        $tokens = Stream::from($data)->tokenize()->reindex()->collect();
        
        self::assertSame($expected, $tokens->get());
        self::assertSame($expected, $tokens->toArray());
        self::assertSame($expected, $tokens->toArrayAssoc());
    }
    
    public function test_sortBy_integer_keys(): void
    {
        $rowset = [
            [2, 'Kate', 35],
            [9, 'Chris', 29],
            [6, 'Joanna', 35],
            [5, 'Chris', 26],
            [7, 'Sue', 17],
            [3, 'Kate', 22],
        ];
        
        self::assertSame([
            [5, 'Chris', 26],
            [9, 'Chris', 29],
            [6, 'Joanna', 35],
            [3, 'Kate', 22],
            [2, 'Kate', 35],
            [7, 'Sue', 17],
        ], Stream::from($rowset)->sortBy(1, 2)->toArray());
        
        self::assertSame([
            [5, 'Chris', 26],
            [9, 'Chris', 29],
            [6, 'Joanna', 35],
            [3, 'Kate', 22],
            [2, 'Kate', 35],
            [7, 'Sue', 17],
        ], Stream::from($rowset)->sortBy('1 asc', '2 asc')->toArray());
        
        self::assertSame([
            [9, 'Chris', 29],
            [5, 'Chris', 26],
            [6, 'Joanna', 35],
            [2, 'Kate', 35],
            [3, 'Kate', 22],
            [7, 'Sue', 17],
        ], Stream::from($rowset)->sortBy(1, '2 desc')->toArray());
        
        self::assertSame([
            [7, 'Sue', 17],
            [2, 'Kate', 35],
            [3, 'Kate', 22],
            [6, 'Joanna', 35],
            [9, 'Chris', 29],
            [5, 'Chris', 26],
        ], Stream::from($rowset)->sortBy('1 desc', '2 desc')->toArray());
        
        self::assertSame([
            [5, 'Chris', 26],
            [9, 'Chris', 29],
        ], Stream::from($rowset)->sortBy('1 desc', '2 desc')->reverse()->limit(2)->toArray());
    }
    
    public function test_extractWhen(): void
    {
        $readings = [
            [2, 5, -1, 7, 3, -1, 1],
            [4, null, -1, 5, null, null, 1],
            [3, 5, null, -1, 5, 4, 3],
        ];
        
        $averages = Stream::from($readings)
            ->extractWhen(Filters::isInt())
            ->extractWhen(Filters::greaterThan(-1))
            ->extractWhen(Filters::lessThan(10))
            ->map(Reducers::average())
            ->toArray();
        
        self::assertSame([
            (2 + 5 + 7 + 3 + 1) / 5,
            (4 + 5 + 1) / 3,
            (3 + 5 + 5 + 4 + 3) / 5,
        ], $averages);
    }
    
    public function test_removeWhen(): void
    {
        $readings = [
            [2, 5, -1, 7, 3, -1, 1],
            [4, null, -1, 5, null, null, 1],
            [3, 5, null, -1, 5, 4, 3],
        ];
        
        $expected = [
            (2 + 5 + 7 + 3 + 1) / 5,
            (4 + 5 + 1) / 3,
            (3 + 5 + 5 + 4 + 3) / 5,
        ];
        
        self::assertSame($expected, Stream::from($readings)
            ->removeWhen(Filters::OR(Filters::NOT('is_int'), Filters::lessThan(0), Filters::greaterThan(9)))
            ->map(Reducers::average())
            ->toArray()
        );
        
        self::assertSame($expected, Stream::from($readings)
            ->removeWhen(Filters::NOT('is_int'))
            ->removeWhen(Filters::lessThan(0))
            ->removeWhen(Filters::greaterThan(9))
            ->map(Reducers::average())
            ->toArray()
        );
    }
    
    public function test_map_row_using_Filter_to_remove_invalid_values_from_them(): void
    {
        $readings = [
            [2, 5, -1, 7, 3, -1, 1],
            [4, null, -1, 5, null, null, 1],
            [3, 5, null, -1, 5, 4, 3],
        ];
        
        $expected = [
            (2 + 5 + 7 + 3 + 1) / 5,
            (4 + 5 + 1) / 3,
            (3 + 5 + 5 + 4 + 3) / 5,
        ];
        
        self::assertSame($expected, Stream::from($readings)
            ->map(Filters::AND('\is_int', Filters::greaterOrEqual(0), Filters::lessOrEqual(9)))
            ->map(Reducers::average())
            ->toArray()
        );
    }
    
    public function test_loop_with_limit(): void
    {
        Stream::of(1)
            ->reindex()
            ->collectIn($collector = Collectors::default())
            ->limit(3)
            ->map(static fn(int $n): int => $n + 1)
            ->loop(true);
        
        self::assertSame(\range(1, 3), $collector->toArray());
    }
    
    public function test_loop_with_until(): void
    {
        Stream::of(1)
            ->collectIn($collector = Collectors::default(), true)
            ->map(static fn(int $n): int => $n + 1)
            ->until(4)
            ->loop(true);
        
        self::assertSame(\range(1, 3), $collector->toArray());
    }
    
    public function test_loop_with_filter(): void
    {
        $stream = Stream::of(1, 3, Producers::sequentialInt(4, 1, 1))
            ->collectIn($collector = Collectors::default(), true)
            ->map(static fn($n): int => $n * 2)
            ->lessThan(10)
            ->loop();
        
        $stream->run();
        
        self::assertSame([1, 2, 4, 8, 3, 6, 4, 8], $collector->toArray());
    }
    
    public function test_concat(): void
    {
        self::assertSame('abc', Stream::from(['a', 'b', 'c'])->reduce('implode')->get());
    }
    
    public function test_each_gather_makes_nested_array(): void
    {
        $data = Producers::getAdapter([1, 2, 3, 4]);
        
        //1 gather
        $result = null;
        $data->stream()->gather()->call(static function (array $all) use (&$result) {
            $result = $all;
        })->run();
        
        self::assertSame([1,2,3,4], $result);
        
        //2 gathers
        $result = null;
        $data->stream()->gather()->gather()->call(static function (array $all) use (&$result) {
            $result = $all;
        })->run();
        
        self::assertSame([[1,2,3,4]], $result);
        
        //3 gathers
        $result = null;
        $data->stream()->gather()->gather()->gather()->call(static function (array $all) use (&$result) {
            $result = $all;
        })->run();
        
        self::assertSame([[[1,2,3,4]]], $result);
    }
    
    public function test_gather_with_preserve_keys(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $result = null;
        $stream->gather()->call(static function (array $all) use (&$result) {
            $result = $all;
        })->run();
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $result);
    }
    
    public function test_gather_with_reindex(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $result = null;
        $stream->reindex()->gather(true)->call(static function (array $all) use (&$result) {
            $result = $all;
        })->run();
        
        self::assertSame([1, 2, 3, 4], $result);
    }
    
    public function test_gather_with_keys_preserve_and_flat_level_1(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $collector = Collectors::default();
        $stream->gather()->flat(1)->collectIn($collector)->run();
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $collector->toArray());
    }
    
    public function test_gather_with_keys_preserve_and_flat_level_full(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $collector = Collectors::default();
        $stream->gather()->flat()->collectIn($collector)->run();
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $collector->toArray());
    }
    
    public function test_gather_with_keys_preserve_and_flat_level_limited(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $collector = Collectors::default();
        $stream->gather()->flat(3)->collectIn($collector)->run();
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $collector->toArray());
    }
    
    public function test_gather_with_reindex_and_flat_level_1(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $collector = Collectors::default();
        $stream->gather(true)->flat(1)->collectIn($collector)->run();
        
        self::assertSame([1, 2, 3, 4], $collector->toArray());
    }
    
    public function test_gather_with_reindex_and_flat_level_full(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $collector = Collectors::default();
        $stream->gather(true)->flat()->collectIn($collector)->run();
        
        self::assertSame([1, 2, 3, 4], $collector->toArray());
    }
    
    public function test_gather_with_reindex_and_flat_level_limited(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $collector = Collectors::default();
        $stream->gather(true)->flat(3)->collectIn($collector)->run();
        
        self::assertSame([1, 2, 3, 4], $collector->toArray());
    }
    
    public function test_gather_pushes_collected_data_to_next_operation_when_stream_is_terminated(): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        
        $collector = Collectors::default();
        $stream->while(Filters::lessThan(3))->gather()->collectIn($collector)->run();
        
        self::assertSame([['a' => 1, 'b' => 2]], $collector->toArray());
    }
    
    public function test_collect_while(): void
    {
        self::assertSame(
            [1, 2],
            Stream::from([1, 2, 3, 4])->collectWhile(Filters::lessThan(3))->toArray()
        );
    }
    
    public function test_collect_until(): void
    {
        self::assertSame(
            [1, 2],
            Stream::from([1, 2, 3, 4])->collectUntil(Filters::greaterThan(2))->toArray()
        );
    }
    
    public function test_make_tuple(): void
    {
        self::assertSame(
            '[[0,1],[1,2]]',
            Stream::from([1, 2, 3, 4])->limit(2)->makeTuple()->toJson()
        );
        
        self::assertSame(
            '[[0,1],[1,2]]',
            Stream::from([1, 2, 3, 4])->makeTuple()->limit(2)->toJson()
        );
        
        self::assertSame(
            '[{"key":1,"value":2},{"key":2,"value":3}]',
            Stream::from([1, 2, 3, 4])->skip(1)->makeTuple(true)->limit(2)->toJson()
        );
    }
    
    public function test_gather_on_empty_stream_keep_keys(): void
    {
        self::assertSame('', Stream::empty()->gather()->toString());
    }
    
    public function test_gather_on_empty_stream_reindex_keys(): void
    {
        self::assertSame('', Stream::empty()->gather(true)->toString());
    }
    
    public function test_reindex_throws_exception_when_step_is_zero(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('step'));
        
        Stream::from([1, 2])->reindex(0, 0)->run();
    }
    
    public function test_reverse_on_empty_stream(): void
    {
        self::assertSame('', Stream::empty()->reverse()->toString());
    }
    
    public function test_shuffle_on_empty_stream(): void
    {
        self::assertSame('', Stream::empty()->shuffle()->toString());
    }
    
    public function test_shuffle_chunked_values(): void
    {
        $data = \range(1, 1000);
        $chunkSize = 100;
        
        $result = Stream::from($data)->shuffle($chunkSize)->toArray();
        
        for ($i = 0; $i < 1000; $i += $chunkSize) {
            self::assertNotSame(\array_slice($data, $i, $chunkSize), \array_slice($result, $i, $chunkSize));
        }
    }
    
    public function test_shuffle_throws_exception_when_chunk_size_is_less_than_1(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('chunkSize'));
        
        Stream::from([1, 2])->shuffle(0)->run();
    }
    
    public function test_SortLimited_on_empty_stream(): void
    {
        self::assertSame('', Stream::empty()->best(10)->toString());
    }
    
    public function test_reindexBy_many_field_use_last_one(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
        ];
        
        $result = Stream::from($rowset)
            ->reindexBy('name')
            ->reindexBy('id')
            ->extract(['name', 'age'])
            ->toArrayAssoc();
        
        $expected = [
            2 => ['name' => 'Kate', 'age' => 35],
            9 => ['name' => 'Chris', 'age' => 26],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_mapKey_will_not_use_mapper_Key_and_map_will_not_use_mapper_Value(): void
    {
        self::assertSame(
            ['a', 'b', 'c'],
            Stream::from(['a', 'b', 'c'])->map(Mappers::value())->mapKey(Mappers::key())->toArrayAssoc()
        );
    }
    
    public function test_map_key_to_value_and_then_value_to_key_is_optimized(): void
    {
        self::assertSame(
            [0, 1, 2],
            Stream::from(['a', 'b', 'c'])->map(Mappers::key())->mapKey(Mappers::value())->toArrayAssoc()
        );
    }
    
    public function test_MapKeyValue_can_use_Mapper_to_compute_value(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
        ];
        
        $result = Stream::from($rowset)
            ->reindexBy('id', true)
            ->toArrayAssoc();
        
        $expected = [
            2 => ['name' => 'Kate', 'age' => 35],
            9 => ['name' => 'Chris', 'age' => 26],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_chunkBy_can_preserve_keys_of_elements_in_stream(): void
    {
        $data = ['a', 'e', 12, 'b', 'd', 8, 9, 6, 'c'];
        $result = Stream::from($data)->chunkBy('is_string')->toArray();
        
        $expected = [
            [0 => 'a', 1 => 'e'],
            [2 => 12],
            [3 => 'b', 4 => 'd'],
            [5 => 8, 6 => 9, 7 => 6],
            [8 => 'c'],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_chunkBy_can_reindex_keys_of_elements_in_stream(): void
    {
        $data = ['a', 'e', 12, 'b', 'd', 8, 9, 6, 'c'];
        $result = Stream::from($data)->chunkBy('is_string', true)->toArray();
        
        $expected = [
            ['a', 'e'],
            [12],
            ['b', 'd'],
            [8, 9, 6],
            ['c'],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_reduce_operation_can_also_count_number_of_elements_in_stream(): void
    {
        $data = ['a', 'b', 'c', 'd', 'e'];
        
        self::assertSame(5, Stream::from($data)->reduce('count')->get());
        self::assertSame(5, Stream::from($data)->reduce('\count')->get());
        self::assertSame(5, Stream::from($data)->reduce(Reducers::count())->get());
    }
    
    public function test_accumulate_can_reindex_keys(): void
    {
        $stream = Stream::from([5,8,2,1,4,2,6,3,7,2,8,4,1,4,5,6,2,3,7])
            ->accumulate(Filters::number()->isEven(), true, Check::VALUE);
        
        $expected = [
            [8,2],
            [4,2,6],
            [2,8,4],
            [4],
            [6,2],
        ];
        
        self::assertSame($expected, $stream->toArray());
    }
    
    public function test_accumulate_can_preserve_keys(): void
    {
        $stream = Stream::from([5,8,2,1,4,2,6,3,7,2,8,4,1,4,5,6,2,3,7])
            ->accumulate(Filters::number()->isEven());
        
        $expected = [
            [1 => 8, 2],
            [4 => 4, 2, 6],
            [9 => 2, 8, 4],
            [13 => 4],
            [15 => 6, 2],
        ];
        
        self::assertSame($expected, $stream->toArray());
    }
    
    public function test_separate_can_preserve_keys(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 22],
            ['id' => 9, 'name' => 'Chris', 'age' => 17],
            ['id' => 6, 'name' => 'Joanna', 'age' => 15],
            ['id' => 5, 'name' => 'Chris', 'age' => 24],
            ['id' => 7, 'name' => 'Sue', 'age' => 18],
        ];
        
        $adults = Stream::from($rowset)
            ->reindexBy('id', true)
            ->separateBy(Filters::filterBy('age', Filters::lessOrEqual(18)))
            ->toArray();
        
        $expected = [
            [
                2 => ['name' => 'Sue', 'age' => 22],
            ], [
                5 => ['name' => 'Chris', 'age' => 24],
            ],
        ];
        
        self::assertSame($expected, $adults);
    }
    
    public function test_mapWhen_with_value_as_mapper_has_no_effect(): void
    {
        $data = ['foo', 'bar'];
        
        $result = Stream::from($data)
            ->mapWhen('is_string', Mappers::value(), Mappers::value())
            ->toArray();
        
        self::assertSame($data, $result);
    }
    
    public function test_mapFieldWhen_with_value_as_mapper_has_no_effect(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 22],
            ['id' => 9, 'name' => 'Chris', 'age' => 17],
        ];
        
        $result = Stream::from($rowset)
            ->mapFieldWhen('name', 'is_string', Mappers::value(), Mappers::value())
            ->toArray();
        
        self::assertSame($rowset, $result);
    }
    
    public function test_stream_can_abort_processing_silently_on_error(): void
    {
        $counter = 0;
        
        $data = Stream::from([1, 5, 2, 7, 9, 2, 4, 7, 8, 3, 2])
            ->onError(OnError::abort())
            ->call(function () use (&$counter) {
                if ($counter++ === 3) {
                    throw new \RuntimeException();
                }
            })
            ->collect()
            ->get();
        
        self::assertSame([1, 5, 2], $data);
    }
    
    public function test_ReverseItemsIterator_can_reindex_keys_with_onerror_handler(): void
    {
        //given
        $items = self::items([
            3 => 'a',
            2 => 'b',
            4 => 'c',
            1 => 'b',
            0 => 'a',
        ]);
        
        //when
        $producer = new ReverseItemsIterator($items, true);
        
        //then
        self::assertSame(['a', 'b', 'c', 'b', 'a'], $producer->stream()->onError(OnError::skip())->toArrayAssoc());
    }
    
    public function test_ReverseNumericalArrayIterator_with_reindex_and_onerror_handler(): void
    {
        $producer = new ReverseNumericalArrayIterator(['a', 'b', 'c', 'd'], true);
        
        self::assertSame(['d', 'c', 'b', 'a'], $producer->stream()->onError(OnError::skip())->toArrayAssoc());
    }
    
    /**
     * @dataProvider getDataForTestIterateProducerWithOnErrorHandler
     */
    #[DataProvider('getDataForTestIterateProducerWithOnErrorHandler')]
    public function test_iterate_producer_with_onerror_handler($producer): void
    {
        self::assertSame(
            [1 => 'a', 3 => 'b', 5 => 'c'],
            Producers::getAdapter($producer)->stream()->onError(OnError::skip())->toArrayAssoc()
        );
    }
    
    public static function getDataForTestIterateProducerWithOnErrorHandler(): \Generator
    {
        $data = [1 => 'a', 3 => 'b', 5 => 'c'];
        
        yield 'ArrayAdapter' => [$data];
        yield 'ArrayIteratorAdapter' => [new \ArrayIterator($data)];
        
        yield 'CircularBufferIterator' => [
            new CircularBufferIterator(self::items([5 => 'c', 1 => 'a', 3 => 'b']), 3, 1)
        ];
        
        yield 'CombinedArrays' => [Producers::combinedFrom([1, 3, 5], ['a', 'b', 'c'])];
        
        yield 'CombinedGeneral' => [
            Producers::combinedFrom(
                Producers::sequentialInt(1, 2),
                Producers::tokenizer(' ', 'a b c')
            )
        ];
        
        yield 'ForwardItemsIterator' => [new ForwardItemsIterator(self::items($data))];
        
        yield 'QueueProducer' => [Producers::queue()->append('a', 1)->append('b', 3)->append('c', 5)];
        
        yield 'ReverseArrayIterator' => [new ReverseArrayIterator(\array_reverse($data, true))];
        
        yield 'ReverseItemsIterator' => [new ReverseItemsIterator(\array_reverse(self::items($data)))];
        
        yield 'MultiProducer' => [
            Producers::multiSourced(
                Producers::queue()->append('a', 1),
                Producers::combinedFrom([3], ['b']),
                new ReverseArrayIterator([5 => 'c']),
            )
        ];
        
        yield 'CallableAdapter' => [
            Producers::getAdapter(static function () use ($data) {
                yield from $data;
            })
        ];
        
        yield 'Flattener' => [Producers::flattener([1 => 'a', [3 => 'b', [5 => 'c']]])];
        
        yield 'ResultCasterAdapter' => [Producers::getAdapter(Stream::from($data)->collect())];
    }
    
    public function test_get_last(): void
    {
        self::assertSame('c', Stream::from(['a', 'b', 'c'])->last()->get());
    }
    
    public function test_stream_with_last_operation(): void
    {
        $tail = Stream::empty()->tail(2)->collect();
        
        $last = Stream::empty()->join(['d', 'e', 'f'])->feed($tail)->last();
        
        Stream::from(['a', 'b', 'c'])->feed($last);
        
        self::assertSame('f', $last->get());
        self::assertSame([1 => 'e', 2 => 'f'], $tail->toArrayAssoc());
    }
    
    public function test_transform_result_of_last_operation_to_iterator(): void
    {
        $data = [1, 2, 3, 4];
        
        $collected = Stream::from($data)
            ->collect()
            ->transform(static fn(array $data): \ArrayIterator => new \ArrayIterator($data));
        
        $actual = [];
        foreach ($collected as $key => $value) {
            $actual[$key] = $value;
        }
        
        self::assertSame($data, $actual);
    }
    
    public function test_accumulate_with_onerror_handler(): void
    {
        $actual = Stream::from(['a', 'b', 1, 'c', 'd'])
            ->accumulate('is_string')
            ->onError(OnError::skip())
            ->toArray();
        
        self::assertSame([
            ['a', 'b'],
            [3 => 'c', 'd']
        ], $actual);
    }
    
    public function test_accumulate_with_onerror_handler_and_reindex(): void
    {
        $actual = Stream::from(['a', 'b', 1, 'c', 'd'])
            ->accumulate('is_string', true)
            ->onError(OnError::skip())
            ->toArray();
        
        self::assertSame([
            ['a', 'b'],
            ['c', 'd']
        ], $actual);
    }
    
    public function test_aggregate_with_one_key_and_onerror_handler(): void
    {
        $actual = Stream::from(['foo', 'a', 'foo', 'b', 'c', 'foo',])
            ->onError(OnError::skip())
            ->flip()
            ->aggregate(['foo'])
            ->toArrayAssoc();
        
        self::assertSame([
            ['foo' => 0],
            ['foo' => 2],
            ['foo' => 5],
        ], $actual);
    }
    
    public function test_aggregate_with_two_keys_and_onerror_handler(): void
    {
        $actual = Stream::from(['foo', 'a', 'bar', 'foo', 'b', 'c', 'foo', 'bar', 'd'])
            ->onError(OnError::skip())
            ->flip()
            ->aggregate(['foo', 'bar'])
            ->toArrayAssoc();
        
        self::assertSame([
            ['foo' => 0, 'bar' => 2],
            ['foo' => 6, 'bar' => 7],
        ], $actual);
    }
    
    public function test_hasOnly_check_value_with_onerror_hander(): void
    {
        $actual = Stream::from([3, 2, 5, 1, 4, 6, 7])
            ->onError(OnError::skip())
            ->hasOnly([1, 5])
            ->get();
        
        self::assertFalse($actual);
    }
    
    public function test_hasOnly_check_key_with_onerror_hander(): void
    {
        $actual = Stream::from([3, 2, 5, 1, 4, 6, 7])
            ->onError(OnError::skip())
            ->hasOnly([1, 5], Check::KEY)
            ->get();
        
        self::assertFalse($actual);
    }
    
    public function test_hasOnly_check_both_with_onerror_hander(): void
    {
        $actual = Stream::from([3, 2, 5, 1, 4, 6, 7])
            ->onError(OnError::skip())
            ->hasOnly([1, 5], Check::BOTH)
            ->get();
        
        self::assertFalse($actual);
    }
    
    public function test_hasOnly_check_any_with_onerror_hander(): void
    {
        $actual = Stream::from([3, 2, 5, 1, 4, 6, 7])
            ->onError(OnError::skip())
            ->hasOnly([1, 5], Check::ANY)
            ->get();
        
        self::assertFalse($actual);
    }
    
    public function test_assert_with_onerror_handler(): void
    {
        $this->expectExceptionObject(AssertionFailed::exception(null, 3, Check::VALUE));
        
        Stream::from([1, 2, 3, null, 4, 5])
            ->assert('is_int')
            ->onError(OnError::skip())
            ->run();
    }
    
    public function test_everyNth_with_onerror_handler(): void
    {
        self::assertSame([1, 3, 5], Stream::from([1, 2, 3, 4, 5, 6])->onError(OnError::skip())->everyNth(2)->toArray());
    }
    
    public function test_extrema_with_onerror_handler_and_limit(): void
    {
        $actual = Stream::from([2, 3, 4, 3, 2, 1, 2, 3, 4, 5, 4, 3, 2, 1])
            ->onError(OnError::skip())
            ->limit(9)
            ->onlyExtrema()
            ->toArray();
        
        self::assertSame([2, 4, 1, 4], $actual);
    }
    
    public function test_filterWhen_with_onerror_handler(): void
    {
        $actual = Stream::from([4, 'a', 2, 5, 'v', 3])
            ->onError(OnError::skip())
            ->filterWhen('is_int', Filters::greaterOrEqual(5))
            ->toArrayAssoc();
        
        self::assertSame([
            1 => 'a',
            3 => 5,
            4 => 'v',
        ], $actual);
    }
    
    public function test_filterWhile_with_onerror_hander(): void
    {
        $actual = Stream::from([7, 3, 5, 'a', 1, 3, 'b'])
            ->onError(OnError::skip())
            ->filterWhile('is_int', Filters::greaterThan(5))
            ->toArray();
        
        self::assertSame([7, 'a', 1, 3, 'b'], $actual);
    }
    
    public function test_gather_with_onerror_handler_and_reindex(): void
    {
        self::assertSame([['a', 'b']], Stream::from(['a', 'b'])->onError(OnError::skip())->gather(true)->toArray());
    }
    
    public function test_gather_with_onerror_handler_and_reindex_empty_string(): void
    {
        self::assertSame([], Stream::from([])->onError(OnError::skip())->gather(true)->toArray());
    }
    
    public function test_sort_gather_onerror_handler_keep_keys(): void
    {
        $actual = Stream::from([5, 2, 3])
            ->onError(OnError::skip())
            ->sort()
            ->gather()
            ->toArray();
        
        self::assertSame([[1 => 2, 2 => 3, 0 => 5,]], $actual);
    }
    
    public function test_sort_gather_onerror_handler_reindex_keys(): void
    {
        $actual = Stream::from([5, 2, 3])
            ->onError(OnError::skip())
            ->rsort()
            ->gather(true)
            ->toArray();
        
        self::assertSame([[5, 3, 2]], $actual);
    }
    
    public function test_tail_gather_reindex_keys_with_onerror_handler(): void
    {
        $actual = Stream::from([5, 2, 4, 1, 3])
            ->onError(OnError::skip())
            ->tail(2)->gather(true)->toArrayAssoc();
        
        self::assertSame([[1, 3]], $actual);
    }
    
    public function test_tail_gather_keep_keys_with_onerror_handler(): void
    {
        $actual = Stream::from([5, 2, 4, 1, 3])
            ->onError(OnError::skip())
            ->tail(2)->gather()->toArrayAssoc();
        
        self::assertSame([[3 => 1, 3]], $actual);
    }
    
    public function test_gather_gather_reindex_keys_with_onerror_handler(): void
    {
        $actual = Stream::from([5, 2])
            ->onError(OnError::skip())
            ->gather(true)
            ->gather(true)
            ->toArrayAssoc();
        
        self::assertSame([[[5, 2]]], $actual);
    }
    
    public function test_gather_gather_keep_keys_with_onerror_handler(): void
    {
        $actual = Stream::from(['a' => 5, 'b' => 2])
            ->onError(OnError::skip())
            ->gather()
            ->gather()
            ->toArrayAssoc();
        
        self::assertSame([[['a' => 5, 'b' => 2]]], $actual);
    }
    
    public function test_hasEvery_any_with_onerror_handler(): void
    {
        self::assertFalse(
            Stream::from(['d', 'c', 'b', 'a'])->onError(OnError::skip())->hasEvery(['e', 2], Check::ANY)->get()
        );
        
        self::assertTrue(
            Stream::from(['a', 'b', 'c', 'd'])->onError(OnError::skip())->hasEvery(['a', 2], Check::ANY)->get()
        );
    }
    
    public function test_MultiMapper_with_onerror_handler_1(): void
    {
        $result = Stream::of('The brOwn quicK PythoN jumps OVER the lAzY Panther')
            ->onError(OnError::skip())
            ->flatMap(Mappers::split())
            ->map([
                'original' => Mappers::value(),
                'uppercase' => '\strtoupper',
                'number' => Mappers::key(),
            ])
            ->find(static fn(array $entry): bool => $entry['original'] === $entry['uppercase']);
        
        self::assertTrue($result->found());
        self::assertSame('{"original":"OVER","uppercase":"OVER","number":5}', $result->toJsonAssoc());
    }
    
    public function test_MultiMapper_with_onerror_handler_2(): void
    {
        $result = Stream::from([5, 7, 2, 5, 1, 3, 4, 2, 8, 9, 1, 2])
            ->onError(OnError::skip())
            ->chunk(3, true)
            ->map([
                'chunk' => Mappers::value(),
                'avg' => Reducers::average(),
            ])
            ->find(Filters::filterBy('avg', 'is_int'));
        
        self::assertTrue($result->found());
        
        self::assertSame([
            'chunk' => [5, 1, 3],
            'avg' => 3,
        ], $result->get());
    }
    
    public function test_extractWhen_with_onerror_handler(): void
    {
        $readings = [
            [2, 5, -1, 7, 3, -1, 1],
            [4, null, -1, 5, null, null, 1],
            [3, 5, null, -1, 5, 4, 3],
        ];
        
        $averages = Stream::from($readings)
            ->onError(OnError::skip())
            ->extractWhen(Filters::isInt())
            ->extractWhen(Filters::greaterThan(-1))
            ->extractWhen(Filters::lessThan(10))
            ->map(Reducers::average())
            ->toArray();
        
        self::assertSame([
            (2 + 5 + 7 + 3 + 1) / 5,
            (4 + 5 + 1) / 3,
            (3 + 5 + 5 + 4 + 3) / 5,
        ], $averages);
    }
    
    public function test_removeWhen_with_onerror_handler(): void
    {
        $readings = [
            [2, 5, -1, 7, 3, -1, 1],
            [4, null, -1, 5, null, null, 1],
            [3, 5, null, -1, 5, 4, 3],
        ];
        
        $expected = [
            (2 + 5 + 7 + 3 + 1) / 5,
            (4 + 5 + 1) / 3,
            (3 + 5 + 5 + 4 + 3) / 5,
        ];
        
        self::assertSame($expected, Stream::from($readings)
            ->onError(OnError::skip())
            ->removeWhen(Filters::OR(Filters::NOT('is_int'), Filters::lessThan(0), Filters::greaterThan(9)))
            ->map(Reducers::average())
            ->toArray()
        );
        
        self::assertSame($expected, Stream::from($readings)
            ->onError(OnError::skip())
            ->removeWhen(Filters::NOT('is_int'))
            ->removeWhen(Filters::lessThan(0))
            ->removeWhen(Filters::greaterThan(9))
            ->map(Reducers::average())
            ->toArray()
        );
    }
    
    public function test_extract_with_onerror_handler(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 8, 'name' => 'Joanna', 'age' => 18],
        ];
        
        $json = Stream::from($rowset)
            ->onError(OnError::skip())
            ->extract(['name', 'age'])
            ->toJson();
        
        self::assertSame('[{"name":"Kate","age":35},{"name":"Chris","age":26},{"name":"Joanna","age":18}]', $json);
    }
    
    public function test_use_Discriminator_as_Mapper_with_onerror_handler(): void
    {
        $result = Stream::from([6, 2, 4, 3, 1, 2, 5])
            ->onError(OnError::skip())
            ->map(Discriminators::evenOdd())
            ->reduce(Reducers::countUnique());
        
        self::assertSame(['even' => 4, 'odd' => 3], $result->toArrayAssoc());
    }
    
    public function test_fork_by_even_and_odd_keys(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g'])
            ->fork(Discriminators::evenOdd(Check::KEY), Reducers::concat())
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => 'aceg',
            'odd' => 'bdf',
        ], $result);
    }
    
    public function test_filter_only_null_both(): void
    {
        $result = Stream::from([[1, 2], [null, 3], [null, null], [4, null], [null, null], [5, null]])
            ->unpackTuple()
            ->filter(Filters::isNull(Check::BOTH))
            ->count()
            ->get();
        
        self::assertSame(2, $result);
    }
    
    public function test_read_interleaved_data_1(): void
    {
        $result = Stream::from([5, 'aaa', 7, 'bbb', 2, 'ccc', 5, 'ddd', 3, 'eee', 6, 'fff'])
            ->skipUntil(3)
            ->skip(1)
            ->first();
            
        self::assertSame('eee', $result->get());
        self::assertSame(9, $result->key());
    }
    
    public function test_read_interleaved_data_2(): void
    {
        $result = Stream::from([5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 3, 'ggg', 'hhh', 8, 'iii', 'jjj'])
            ->skipUntil(9)
            ->skip(1)
            ->limit(2)
            ->collect();
            
        self::assertSame([7 => 'eee', 8 => 'fff'], $result->get());
    }
    
    public function test_read_interleaved_data_3(): void
    {
        $result = Stream::from([5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'])
            ->chunk(3, true)
            ->filterBy(0, 2)
            ->extract([1, 2])
            ->collect();
            
        self::assertSame([
            1 => [1 => 'ccc', 2 => 'ddd'],
            3 => [1 => 'ggg', 2 => 'hhh'],
        ], $result->get());
    }
    
    public function test_read_interleaved_data_4(): void
    {
        $result = Stream::from([5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'])
            ->chunk(3, true)
            ->filterBy(0, 2)
            ->extract([1, 2])
            ->collect(true);
            
        self::assertSame([
            0 => [1 => 'ccc', 2 => 'ddd'],
            1 => [1 => 'ggg', 2 => 'hhh'],
        ], $result->get());
    }
    
    public function test_read_interleaved_data_5(): void
    {
        $result = Stream::from([5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'])
            ->chunk(3, true)
            ->filterBy(0, 2)
            ->extract([1, 2])
            ->map('\array_values')
            ->collect(true);
            
        self::assertSame([
            ['ccc', 'ddd'],
            ['ggg', 'hhh'],
        ], $result->get());
    }
    
    public function test_read_interleaved_data_6(): void
    {
        $result = Stream::from([5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'])
            ->chunk(3, true)
            ->filterBy(0, 2)
            ->extract([1, 2])
            ->flatMap('\array_values')
            ->collect(true);
            
        self::assertSame(['ccc', 'ddd', 'ggg', 'hhh'], $result->get());
    }
    
    public function test_read_interleaved_data_7(): void
    {
        $result = Stream::from([5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'])
            ->chunk(3)
            ->filter(static fn(array $p): bool => $p[\array_key_first($p)] === 2)
            ->map(static function (array $p): array {
                unset($p[\array_key_first($p)]);
                return $p;
            })
            ->collect();
            
        self::assertSame([
            [4 => 'ccc', 'ddd'],
            [10 => 'ggg', 'hhh'],
        ], $result->toArray());
    }
    
    public function test_read_interleaved_data_8(): void
    {
        $result = Stream::from([5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'])
            ->chunk(3)
            ->mapKey('\array_key_first')
            ->filter(static fn(array $v, int $k): bool => $v[$k] === 2)
            ->map(static function (array $v, int $k): array {
                unset($v[$k]);
                return $v;
            })
            ->collect();
            
        self::assertSame([
            [4 => 'ccc', 'ddd'],
            [10 => 'ggg', 'hhh'],
        ], $result->toArray());
    }
    
    public function test_read_interleaved_data_9(): void
    {
        $result = Stream::from([5, 'aaa', 2, 'bbb', 9, 'ccc', 3, 'ddd', 8, 'eee'])
            ->filter(9)
            ->readNext()
            ->first();
            
        self::assertSame('ccc', $result->get());
        self::assertSame(5, $result->key());
        
        self::assertSame([5 => 'ccc'], $result->toArrayAssoc());
        self::assertSame(['ccc'], $result->toArray());
    }
    
    public function test_read_interleaved_data_10(): void
    {
        $data = [5, 'aaa', 2, 'bbb', 9, 'ccc', 2, 'ddd', 8, 'eee', 2, 'fff'];
        
        $result = Stream::from($data)
            ->filter(2)
            ->readNext()
            ->collect();
            
        self::assertSame([
            3 => 'bbb',
            7 => 'ddd',
            11 => 'fff',
        ], $result->toArrayAssoc());
    }
    
    public function test_read_interleaved_data_11(): void
    {
        $data = [5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'];

        $result = Stream::from($data)
            ->filter(2)
            ->readMany(2)
            ->chunk(2)
            ->collect();

        self::assertSame([
            [4 => 'ccc', 'ddd'],
            [10 => 'ggg', 'hhh'],
        ], $result->toArray());
    }
    
    public function test_read_interleaved_data_11_reindex_keys(): void
    {
        $data = [5, 'aaa', 'bbb', 2, 'ccc', 'ddd', 9, 'eee', 'fff', 2, 'ggg', 'hhh', 8, 'iii', 'jjj'];

        $result = Stream::from($data)
            ->filter(2)
            ->readMany(2, true)
            ->chunk(2)
            ->collect();

        self::assertSame([
            ['ccc', 'ddd'],
            ['ggg', 'hhh'],
        ], $result->toArray());
    }
    
    public function test_read_interleaved_data_12(): void
    {
        $reg = Registry::new();
        
        $result = Stream::from($this->dataForInterleavedReadMany())
            ->remember($reg->valueKey('quantity', 'index'))
            ->readMany($reg->read('quantity'))
            ->fork($reg->read('index'), Reducers::sum())
            ->toArrayAssoc();
        
        $this->examineInterleavedReadManyResult($result);
    }
    
    public function test_read_interleaved_data_13(): void
    {
        $entry = Registry::new()->valueKey();
        
        $result = Stream::from($this->dataForInterleavedReadMany())
            ->remember($entry)
            ->readMany($entry->value())
            ->fork($entry->key(), Reducers::sum())
            ->toArrayAssoc();
        
        $this->examineInterleavedReadManyResult($result);
    }
    
    public function test_read_interleaved_data_14(): void
    {
        $entry = Registry::new()->entry(Check::BOTH);
        
        $result = Stream::from($this->dataForInterleavedReadMany())
            ->remember($entry)
            ->readMany($entry->value())
            ->fork($entry->key(), Reducers::sum())
            ->toArrayAssoc();
        
        $this->examineInterleavedReadManyResult($result);
    }
    
    public function test_read_interleaved_data_15(): void
    {
        $result = Stream::from($this->dataForInterleavedReadMany())
            ->putValueKeyIn($quantity, $key)
            ->readMany(IntNum::readFrom($quantity))
            ->fork(Discriminators::readFrom($key), Reducers::sum())
            ->toArrayAssoc();
        
        $this->examineInterleavedReadManyResult($result);
    }
    
    public function test_read_interleaved_data_16(): void
    {
        $entry = Registry::new()->entry(Check::BOTH);
        
        $result = Stream::from($this->dataForInterleavedReadMany())
            ->remember($entry)
            ->readMany($entry->value())
            ->categorize($entry->key())
            ->map(Reducers::sum())
            ->toArrayAssoc();
            
        $this->examineInterleavedReadManyResult($result);
    }
    
    public function test_read_interleaved_data_17(): void
    {
        $item = Memo::full();
        
        $result = Stream::from($this->dataForInterleavedReadMany())
            ->remember($item)
            ->readMany($item->value())
            ->fork($item->key(), Reducers::sum())
            ->toArrayAssoc();
        
        $this->examineInterleavedReadManyResult($result);
    }
    
    private function dataForInterleavedReadMany(): array
    {
        return [
            2, -4, 7, //3
            1, 9, //9
            4, 3, -2, 6, 8, //15
            3, 2, 5, -1, //6
            5, 0, 8, 2, 3, //13
        ];
    }
    
    private function examineInterleavedReadManyResult(array $result): void
    {
        self::assertSame([
            0 => 3,
            3 => 9,
            5 => 15,
            10 => 6,
            14 => 13,
        ], $result);
    }
    
    public function test_filter_readNext_filter(): void
    {
        $result = Stream::from([5, 'a', 2, 'ccc', 9, 'e', 2, 'g', 8, 'jjj', 2, 'kkk', 1, 'll', 2])
            ->filter(Filters::number()->eq(2))
            ->readNext()
            ->filter(Filters::length()->eq(3));
            
        self::assertSame([3 => 'ccc', 11 => 'kkk'], $result->toArrayAssoc());
    }
    
    public function test_filter_readNext_tokenize_sort(): void
    {
        $result = Stream::from([5, 'a', 2, 'c d e', 9, 'e', 2, 'g h', 8, 'jjj', 2, 'k m n', 1, 'll', 2])
            ->filter(Filters::number()->eq(2))
            ->readNext()
            ->tokenize()
            ->rsort();

        self::assertSame('nmkhgedc', $result->toString(''));
    }
    
    public function test_consequtive_readNext(): void
    {
        $result = Stream::from([5, 'a', 2, 'c', 'd', 'e g h', 8, 'jjj', 2, 'o', 'p', 'k m n', 1, 'll', 2, 'w', 'a'])
            ->filter(Filters::number()->eq(2))
            ->readNext()
            ->readNext()
            ->readNext()
            ->tokenize()
            ->rsort();
        
        self::assertSame('nmkhge', $result->toString(''));
    }
    
    public function test_multiple_readNext(): void
    {
        $result = Stream::from([5, 'a', 2, 'c', 'd', 'e g h', 8, 'jjj', 2, 'o', 'p', 'k m n', 1, 'll', 2, 'w', 'a'])
            ->filter(Filters::number()->eq(2))
            ->readNext(3)
            ->tokenize()
            ->rsort();
        
        self::assertSame('nmkhge', $result->toString(''));
    }
    
    public function test_readWhile_1(): void
    {
        $result = Stream::from([5, 'a', 2, 'w', 'a',  2, 'c', 'd', 'e g h', 8, 'jjj', 2, 'o', 'p', 'k m n', 1, 'll'])
            ->filter(2)
            ->readWhile('is_string')
            ->tokenize()
            ->sort();
        
        self::assertSame('acdeghkmnopw', $result->toString(''));
    }
    
    public function test_readWhile_2(): void
    {
        $data = [1, 'a', 2, 'b', 'c', 1, 'd', 2, 'e', 'f', 3, 'g', 'h', 'i'];
        
        self::assertSame(
            ['b', 'c', 'e', 'f'],
            Stream::from($data)->filter(2)->readWhile('is_string')->toArray()
        );
    }
    
    public function test_readWhile_3(): void
    {
        $result = Stream::from([5, 'a', 2, 'w', 'a',  2, 'c', 'd', 'e g h', 8, 'jjj', 2, 'o', 'p', 'k m n', 1, 'll'])
            ->filter(2)
            ->countIn($numOfSeries)
            ->readWhile('is_string', null, true)
            ->categorize(Discriminators::readFrom($numOfSeries));
        
        self::assertSame([
            1 => ['w', 'a'],
            2 => ['c', 'd', 'e g h'],
            3 => ['o', 'p', 'k m n']
        ], $result->toArrayAssoc());
    }
    
    public function test_readUntil_1(): void
    {
        $result = Stream::from([5, 'a', 2, 'c', 'd', 'e g h', 8, 'jjj', 2, 'o', 'p', 2, 'w', 'a', 'k m n', 1, 'll'])
            ->filter(2)
            ->readUntil('is_int')
            ->tokenize()
            ->sort();
        
        self::assertSame('acdeghkmnopw', $result->toString(''));
    }
    
    public function test_readUntil_2(): void
    {
        $data = [1, 'a', 2, 'b', 'c', 1, 'd', 2, 'e', 'f', 3, 'g', 'h', 'i'];
        
        self::assertSame(
            ['b', 'c', 'e', 'f'],
            Stream::from($data)->filter(2)->readUntil('is_int')->toArray()
        );
    }
    
    public function test_readUntil_3(): void
    {
        $result = Stream::from([5, 'a', 2, 'c', 'd', 'e g h', 8, 'jjj', 2, 'o', 'p', 2, 'w', 'a', 'k m n', 1, 'll'])
            ->filter(2)
            ->countIn($numOfSeries)
            ->readUntil('is_int', null, true)
            ->categorize(Discriminators::readFrom($numOfSeries));
        
        self::assertSame([
            1 => ['c', 'd', 'e g h'],
            2 => ['o', 'p'],
            3 => ['w', 'a', 'k m n']
        ], $result->toArrayAssoc());
    }
    
    public function test_tokenize_before_readNext(): void
    {
        $result = Stream::from(['aaa bbb ccc', 'ddd eee', 'fff'])
            ->tokenize()
            ->readNext()
            ->toArray();
        
        self::assertSame(['bbb', 'ddd', 'fff'], $result);
    }
    
    public function test_find_sequence_in_stream_using_window_1(): void
    {
        $result = Stream::from(['q', 'w', 'e', 'r', 't', 'y', 'u', 'i'])
            ->window(3)
            ->concat('')
            ->find(Filters::same('rty'));
        
        self::assertTrue($result->found());
        self::assertSame(3, $result->key());
    }
    
    public function test_find_sequence_in_stream_using_window_2(): void
    {
        $result = Stream::from(['q', 'w', 'e', 'r', 't', 'y', 'u', 'i'])
            ->window(3)
            ->concat('')
            ->find(Filters::same('ry'));
        
        self::assertFalse($result->found());
    }
    
    public function test_find_sequence_in_stream_using_window_3(): void
    {
        $result = Stream::from(['q', 'w', 'e', 'r', 't', 'y', 'u', 'i'])
            ->window(3, 1, true)
            ->find(['r', 't', 'y']);
        
        self::assertTrue($result->found());
        self::assertSame(3, $result->key());
    }
    
    public function test_find_sequence_in_stream_using_readNext_1(): void
    {
        $result = Stream::from(['q', 'w', 'e', 'r', 't', 'y', 'u', 'i'])
            ->filter(Filters::same('r'))
            ->putIn($atKey, Check::KEY)
            ->readNext()
            ->filter(Filters::same('t'))
            ->readNext()
            ->filter(Filters::same('y'))
            ->first();
        
        self::assertTrue($result->found());
        self::assertSame(3, $atKey);
    }
    
    public function test_find_sequence_in_stream_using_readNext_2(): void
    {
        $result = Stream::from(['q', 'w', 'e', 'r', 't', 'y', 'u', 'i'])
            ->filter(Filters::same('r'))
            ->readNext()
            ->filter(Filters::same('o'))
            ->readNext()
            ->filter(Filters::same('y'))
            ->first();
        
        self::assertFalse($result->found());
    }
    
    public function test_readMany_on_empty_stream(): void
    {
        self::assertEmpty(Stream::empty()->readMany(2)->toArray());
    }
    
    public function test_readNext_on_empty_stream(): void
    {
        self::assertEmpty(Stream::empty()->readNext()->toArray());
    }
    
    public function test_readWhile_on_empty_stream(): void
    {
        self::assertEmpty(Stream::empty()->readWhile('is_string')->toArray());
    }
    
    public function test_readUntil_on_empty_stream(): void
    {
        self::assertEmpty(Stream::empty()->readUntil('is_string')->toArray());
    }
    
    public function test_readNext_as_first_operation_in_stream(): void
    {
        $data = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        
        self::assertSame(['b', 'd', 'f', 'h'], Stream::from($data)->readNext(1)->toArray());
        self::assertSame(['c', 'f'], Stream::from($data)->readNext(2)->toArray());
        self::assertSame(['d', 'h'], Stream::from($data)->readNext(3)->toArray());
        self::assertSame(['e'], Stream::from($data)->readNext(4)->toArray());
        self::assertSame(['h'], Stream::from($data)->readNext(7)->toArray());
        self::assertSame([], Stream::from($data)->readNext(8)->toArray());
    }
    
    public function test_readMany_as_first_operation_in_stream(): void
    {
        $data = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        
        self::assertSame(['b', 'd', 'f', 'h'], Stream::from($data)->readMany(1)->toArray());
        self::assertSame(['b', 'c', 'e', 'f', 'h'], Stream::from($data)->readMany(2)->toArray());
        self::assertSame(['b', 'c', 'd', 'f', 'g', 'h'], Stream::from($data)->readMany(3)->toArray());
        self::assertSame(['b', 'c', 'd', 'e', 'g', 'h'], Stream::from($data)->readMany(4)->toArray());
        self::assertSame(['b', 'c', 'd', 'e', 'f', 'h'], Stream::from($data)->readMany(5)->toArray());
        self::assertSame(['b', 'c', 'd', 'e', 'f', 'g'], Stream::from($data)->readMany(6)->toArray());
        self::assertSame(['b', 'c', 'd', 'e', 'f', 'g', 'h'], Stream::from($data)->readMany(7)->toArray());
        self::assertSame(['b', 'c', 'd', 'e', 'f', 'g', 'h'], Stream::from($data)->readMany(8)->toArray());
    }
    
    public function test_readWhile_as_first_operation_in_stream_keep_keys(): void
    {
        $data = ['z', 1, 'a', 2, 'b', 'c', 1, 'd', 2, 'e', 'f', 3, 'g', 'h', 'i'];
        
        self::assertSame(
            ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'],
            Stream::from($data)->readWhile('is_string')->toArray()
        );
    }
    
    public function test_readUntil_as_first_operation_in_stream_keep_keys(): void
    {
        $data = ['z', 1, 'a', 2, 'b', 'c', 1, 'd', 2, 'e', 'f', 3, 'g', 'h', 'i'];
        
        self::assertSame(
            ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'],
            Stream::from($data)->readUntil('is_int')->toArray()
        );
    }
    
    public function test_readWhile_as_first_operation_in_stream_reindex_keys(): void
    {
        $data = ['z', 1, 'a', 2, 'b', 'c', 1, 'd', 2, 'e', 'f', 3, 'g', 'h', 'i'];
        
        self::assertSame(
            ['g', 'h', 'i'],
            Stream::from($data)->readWhile('is_string', null, true)->toArrayAssoc()
        );
    }
    
    public function test_readUntil_as_first_operation_in_stream_reindex_keys(): void
    {
        $data = ['z', 1, 'a', 2, 'b', 'c', 1, 'd', 2, 'e', 'f', 3, 'g', 'h', 'i'];
        
        self::assertSame(
            ['g', 'h', 'i'],
            Stream::from($data)->readUntil('is_int', null, true)->toArrayAssoc()
        );
    }
    
    /**
     * @return Item[]
     */
    private static function items(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = new Item($key, $value);
        }
        
        return $result;
    }
}