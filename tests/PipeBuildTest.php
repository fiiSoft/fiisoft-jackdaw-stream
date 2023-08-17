<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Reverse;
use FiiSoft\Jackdaw\Operation\Shuffle;
use FiiSoft\Jackdaw\Operation\Sort;
use FiiSoft\Jackdaw\Operation\SortLimited;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class PipeBuildTest extends TestCase
{
    public function test_FilterMany(): void
    {
        $result = Stream::from(['a', 'b', 'c', 1, 2, 3, 4, 5, 6])
            ->onlyIntegers()
            ->greaterThan(2)
            ->lessThan(5)
            ->toArrayAssoc();
        
        self::assertSame([5 => 3, 4], $result);
    }
    
    public function test_Reverse_FilterSingle(): void
    {
        $data = ['a', 1, 'b', 2, 'c', 3];
        
        $result = Stream::from($data)->reverse()->onlyStrings()->without(['a'])->toArrayAssoc();
        
        self::assertSame([4 => 'c', 2 => 'b'], $result);
    }
    
    public function test_Shuffle_FilterSingle(): void
    {
        $result = Stream::from(['a', 1, 'b', 2, 'c', 3])->shuffle()->onlyStrings()->toArrayAssoc();
        
        self::assertCount(3, $result);
        \sort($result);
        
        self::assertSame(['a', 'b', 'c'], $result);
    }
    
    public function test_Sort_FilterSingle(): void
    {
        $result = Stream::from(['a', 1, 'b', 2, 'c', 3])->sort()->onlyStrings()->toArrayAssoc();
        
        self::assertSame([0 => 'a', 2 => 'b', 4 => 'c'], $result);
    }
    
    public function test_best_FilterSingle(): void
    {
        $result = Stream::from([6, 1, 4, 3, 2, 5])->best(4)->filter(Filters::number()->isEven())->toArrayAssoc();
        
        self::assertSame([4 => 2, 2 => 4], $result);
    }
    
    public function test_worst_FilterSingle(): void
    {
        $result = Stream::from([6, 1, 4, 3, 2, 5])->worst(4)->filter(Filters::number()->isEven())->toArrayAssoc();
        
        self::assertSame([0 => 6, 2 => 4], $result);
    }
    
    public function test_Unique_FiltlerSingle(): void
    {
        $data = [1, 'a', 1, 'a', 'b', 2, 3, 'c', 3, 'c', 2, 'a', 1];
        
        $result = Stream::from($data)->unique()->onlyStrings()->toArrayAssoc();
        
        self::assertSame([1 => 'a', 4 => 'b', 7 => 'c'], $result);
    }
    
    public function test_Map_with_Value(): void
    {
        self::assertSame(['a', 'b'], Stream::from(['a', 'b'])->map(Mappers::value())->toArrayAssoc());
    }
    
    public function test_MapKey_with_Key(): void
    {
        $result = Stream::from(['a', 'b'])->mapKey(Mappers::key())->toArrayAssoc();
        self::assertSame(['a', 'b'], $result);
    }
    
    public function test_MapKey_with_Value_then_Map_with_Key(): void
    {
        $result = Stream::from(['a' => 1, 'b' => 2])
            ->mapKey(Mappers::value())
            ->map(Mappers::key())
            ->toArrayAssoc();
        
        self::assertSame([1 => 1, 2 => 2], $result);
    }
    
    public function test_MapMany(): void
    {
        $result = Stream::from(['a' => 1, 'b' => 2])
            ->map(static fn(int $v): int => $v * 2)
            ->map(static fn(int $v): int => $v + 5)
            ->map(static fn(int $v): int => $v - 2)
            ->toArrayAssoc();
        
        self::assertSame(['a' => 5, 'b' => 7], $result);
    }
    
    public function test_Map_with_Tokenize_Flat(): void
    {
        $result = Stream::from(['this is', 'the way'])->map(Mappers::tokenize())->flat()->toArray();
        
        self::assertSame(['this', 'is', 'the', 'way'], $result);
    }
    
    /**
     * @dataProvider getDataForTestLimitTail
     */
    public function test_Limit_Tail(int $limit, int $tail, array $expected): void
    {
        $data = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];
        
        self::assertSame($expected, Stream::from($data)->limit($limit)->tail($tail)->toArray());
    }
    
    public function getDataForTestLimitTail(): array
    {
        return [
            //limit, tail, expected
            [1, 1, ['a']],
            [1, 3, ['a']],
            [5, 1, ['e']],
            [5, 3, ['c', 'd', 'e']],
            [3, 3, ['a', 'b', 'c']],
            [3, 5, ['a', 'b', 'c']],
            [1, 5, ['a']],
        ];
    }
    
    public function test_Limit_Limit(): void
    {
        self::assertSame([1, 2], Stream::from([1, 2, 3, 4, 5])->limit(4)->limit(2)->toArrayAssoc());
    }
    
    public function test_Limit_First(): void
    {
        self::assertSame([0 => 3], Stream::from([3, 6, 1, 2, 5])->limit(3)->first()->toArrayAssoc());
    }
    
    public function test_Gather_Reindex_default(): void
    {
        self::assertSame([['a', 'b']], Stream::from(['a', 'b'])->gather()->reindex()->toArrayAssoc());
    }
    
    public function test_Gather_Reindex_custom(): void
    {
        self::assertSame([1 => ['a', 'b']], Stream::from(['a', 'b'])->gather()->reindex(1)->toArrayAssoc());
    }
    
    public function test_Gather_Flat(): void
    {
        self::assertSame(['a', 'b', 'c'], Stream::from(['a', 'b', 'c'])->gather()->flat(1)->toArrayAssoc());
    }
    
    public function test_Shuffle_Sort(): void
    {
        self::assertSame([1, 2, 3, 4, 5, 6], Stream::from([3, 6, 2, 5, 1, 4])->shuffle()->sort()->toArray());
    }
    
    public function test_Shuffle_SortLimited(): void
    {
        self::assertSame([1, 2, 3, 4, 5, 6], Stream::from([3, 6, 2, 5, 1, 4])->shuffle()->best(10)->toArray());
        self::assertSame([1, 2, 3], Stream::from([3, 6, 2, 5, 1, 4])->shuffle()->best(3)->toArray());
    }
    
    public function test_Reverse_Sort(): void
    {
        self::assertSame([1, 2, 3, 4, 5, 6], Stream::from([3, 6, 2, 5, 1, 4])->shuffle()->sort()->toArray());
    }
    
    public function test_Reverse_SortLimited(): void
    {
        self::assertSame([1, 2, 3, 4, 5, 6], Stream::from([3, 6, 2, 5, 1, 4])->shuffle()->best(10)->toArray());
        self::assertSame([1, 2, 3], Stream::from([3, 6, 2, 5, 1, 4])->shuffle()->best(3)->toArray());
    }
    
    public function getDataForTestShuffleReverseSortingOperation(): array
    {
        return [
            'Shuffle-Sort' => [new Shuffle(), new Sort()],
            'Shuffle-SortLimited' => [new Shuffle(), new SortLimited(100)],
            'Reverse-Sort' => [new Reverse(), new Sort()],
            'Reverse-SortLimited' => [new Reverse(), new SortLimited(100)],
        ];
    }
    
    public function test_SendTo_SendTo(): void
    {
        $counter = Consumers::counter();
        
        Stream::from(['a', 'b'])->call($counter)->call($counter)->run();
        
        self::assertSame(4, $counter->count());
    }
    
    public function test_Segregate_Limit(): void
    {
        $result = Stream::from([4, 2, 3, 1, 5, 2, 3, 1, 5, 2, 4])->segregate()->limit(2)->toArrayAssoc();
        
        self::assertSame([
            [3 => 1, 7 => 1],
            [1 => 2, 5 => 2, 9 => 2],
        ], $result);
    }
    
    public function test_Segregate_First(): void
    {
        $result = Stream::from([4, 2, 3, 1, 5, 2, 3, 1, 5, 2, 4])->segregate()->first()->toArrayAssoc();
        
        self::assertSame([3 => 1, 7 => 1], $result);
    }
    
    /**
     * @dataProvider getDataForTestSegregateTail
     */
    public function test_Segregate_Tail(int $buckets, int $tail, array $expected): void
    {
        $data = [4, 2, 3, 1, 4, 2, 5, 3, 2, 4, 5, 3, 6, 2];
        
        $result = Stream::from($data)->segregate($buckets)->tail($tail)->toArray();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataForTestSegregateTail(): array
    {
        $full = [
            [3 => 1],
            [1 => 2, 5 => 2, 8 => 2, 13 => 2],
            [2 => 3, 7 => 3, 11 => 3],
            [0 => 4, 4 => 4, 9 => 4],
            [6 => 5, 10 => 5],
            [12 => 6],
        ];
        
        return [
            //buckets, tail, expected
            [10, 10, $full],
            [1, 3, [$full[0]]],
            [3, 3, \array_slice($full, 0, 3)],
            [5, 2, \array_slice($full, 3, 2)],
            [3, 1, [$full[2]]],
        ];
    }
    
    /**
     * @dataProvider getDataForTestSortLimitedTail
     */
    public function test_SortLimited_Tail(int $limit, int $tail, array $expected): void
    {
        $result = Stream::from([4, 2, 3, 1, 4, 2, 5, 3, 4, 5, 3, 6])->best($limit)->tail($tail)->toArray();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataForTestSortLimitedTail(): array
    {
        return [
            //limit, tail, expected
            [10, 10, [1, 2, 2, 3, 3, 3, 4, 4, 4, 5]],
            [1, 3, [1]],
            [3, 3, [1, 2, 2]],
            [5, 2, [3, 3]],
            [3, 1, [2]],
        ];
    }
    
    public function test_SortLimited_Limit(): void
    {
        $result = Stream::from([4, 2, 3, 1, 5, 2, 3, 1, 5, 2, 4])->best(100)->limit(6)->toArray();
        
        self::assertSame([1, 1, 2, 2, 2, 3], $result);
    }
    
    public function test_SortLimited_First(): void
    {
        $result = Stream::from([4, 2, 3, 1, 5])->best(10)->first()->toArrayAssoc();
        
        self::assertSame([3 => 1], $result);
    }
    
    public function test_Sort_Last(): void
    {
        $result = Stream::from([4, 2, 6, 3, 1, 5])->sort()->last()->toArrayAssoc();
        
        self::assertSame([2 => 6], $result);
    }
    
    public function test_Sort_Limit(): void
    {
        $result = Stream::from([4, 2, 3, 1, 5, 2, 3, 1, 5, 2, 4])->sort()->limit(6)->toArray();
        
        self::assertSame([1, 1, 2, 2, 2, 3], $result);
    }
    
    public function test_Reverse_Tail(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2];
        
        $result = Stream::from($data)->reverse()->tail(3)->toArrayAssoc();
        
        self::assertSame(['c' => 3, 'b' => 2, 'a' => 4], $result);
    }
    
    public function test_Reverse_First(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2, 'g' => 3, 'h' => 1, 'i' => 5, 'j' => 2];
        
        $result = Stream::from($data)->reverse()->first()->toArrayAssoc();
        
        self::assertSame(['j' => 2], $result);
    }
    
    public function test_Reverse_Last(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2, 'g' => 3, 'h' => 1, 'i' => 5, 'j' => 2];
        
        $result = Stream::from($data)->reverse()->last()->toArrayAssoc();
        
        self::assertSame(['a' => 4], $result);
    }
    
    public function test_Reverse_Limit(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2, 'g' => 3, 'h' => 1, 'i' => 5, 'j' => 2];
        
        $result = Stream::from($data)->reverse()->limit(6)->toArrayAssoc();
        
        self::assertSame(['j' => 2, 'i' => 5, 'h' => 1, 'g' => 3, 'f' => 2, 'e' => 5,], $result);
    }
    
    public function test_Reverse_Reverse(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2, 'g' => 3, 'h' => 1, 'i' => 5, 'j' => 2];
        
        $result = Stream::from($data)->reverse()->reverse()->toArrayAssoc();
        
        self::assertSame($data, $result);
    }
    
    public function test_Limit_one_Reverse(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2, 'g' => 3, 'h' => 1, 'i' => 5, 'j' => 2];
        
        self::assertSame(['a' => 4], Stream::from($data)->limit(1)->reverse()->toArrayAssoc());
    }
    
    public function test_Segregate_one_Reverse(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2, 'g' => 3, 'h' => 1, 'i' => 5, 'j' => 2];
        
        self::assertSame([['d' => 1, 'h' => 1]], Stream::from($data)->segregate(1)->reverse()->toArrayAssoc());
    }
    
    public function test_SortLimited_one_Reverse(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5, 'f' => 2, 'g' => 3, 'h' => 1, 'i' => 5, 'j' => 2];
        
        self::assertSame(['d' => 1], Stream::from($data)->best(1)->reverse()->toArrayAssoc());
    }
    
    public function test_Sort_Reverse(): void
    {
        $data = ['b' => 2, 'c' => 3, 'd' => 1];
        
        self::assertSame(['c' => 3, 'b' => 2, 'd' => 1], Stream::from($data)->sort()->reverse()->toArrayAssoc());
    }
    
    public function test_Sort_Tail(): void
    {
        $result = Stream::from([6, 2, 5, 3, 4, 1, 7, 8])->sort()->tail(3)->toArrayAssoc();
        
        self::assertSame([0 => 6, 6 => 7, 8], $result);
    }
    
    public function test_Sort_First(): void
    {
        $result = Stream::from([6, 2, 5, 3, 4, 1, 7, 8])->sort()->first()->toArrayAssoc();
        
        self::assertSame([5 => 1], $result);
    }
    
    public function test_SortLimited_Reverse(): void
    {
        $data = ['a' => 4, 'b' => 2, 'c' => 3, 'd' => 1, 'e' => 5];
        
        self::assertSame(['d' => 1, 'b' => 2, 'c' => 3], Stream::from($data)->best(3)->toArrayAssoc());
        self::assertSame(['c' => 3, 'b' => 2, 'd' => 1], Stream::from($data)->best(3)->reverse()->toArrayAssoc());
    }
    
    public function test_Skip_Skip(): void
    {
        $result = Stream::from([4, 2, 3, 1, 5, 2, 3, 1, 5, 2, 4])->skip(2)->skip(3)->toArrayAssoc();
        
        self::assertSame([5 => 2, 3, 1, 5, 2, 4], $result);
    }
    
    public function test_MapKey_Reindex(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->mapKey(Mappers::value())->reindex(1, 2)->toArrayAssoc();
        self::assertSame([1 => 'a', 3 => 'b', 5 => 'c', 7 => 'd'], $result);
    }
    
    public function test_Flip_Flip(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->flip()->flip()->toArrayAssoc();
        self::assertSame(['a', 'b', 'c', 'd'], $result);
    }
    
    public function test_Flip_MapKey_with_Value(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->flip()->mapKey(Mappers::value())->toArrayAssoc();
        self::assertSame([0, 1, 2, 3], $result);
    }
    
    public function test_Flip_Map_with_Key(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->flip()->map(Mappers::key())->toArrayAssoc();
        self::assertSame(['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd'], $result);
    }
    
    public function test_MapKey_with_Value_Flip(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->mapKey(Mappers::value())->flip()->toArrayAssoc();
        self::assertSame(['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd'], $result);
    }
    
    public function test_Map_with_Key_Flip(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->map(Mappers::key())->flip()->toArrayAssoc();
        self::assertSame([0, 1, 2, 3], $result);
    }
    
    public function test_Map_with_Key_MapKey_with_Value(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->map(Mappers::key())->mapKey(Mappers::value())->toArrayAssoc();
        self::assertSame([0, 1, 2, 3], $result);
    }
    
    public function test_MapKey_MapKey(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])
            ->mapKey(static fn($_, int $k): int => $k + 1)
            ->mapKey(static fn($_, int $k): int => $k * 2)
            ->toArrayAssoc();
        
        self::assertSame([2 => 'a', 4 => 'b', 6 => 'c', 8 => 'd'], $result);
    }
    
    public function test_MapKey_with_Value_MapKey_with_FieldValue(): void
    {
        $data = [
            ['key' => 3], ['key' => 2], ['key' => 4]
        ];
        
        $result = Stream::from($data)
            ->mapKey(Mappers::value())
            ->mapKey(Mappers::fieldValue('key'))
            ->toArrayAssoc();
        
        self::assertSame([
            3 => ['key' => 3], 2 => ['key' => 2], 4 => ['key' => 4]
        ], $result);
    }
    
    public function test_Tail_Tail(): void
    {
        self::assertSame([5 => 3, 4, 0], Stream::from([3, 2, 7, 1, 5, 3, 4, 0])->tail(5)->tail(3)->toArrayAssoc());
    }
    
    public function test_Tail_Last(): void
    {
        self::assertSame([7 => 0], Stream::from([3, 2, 7, 1, 5, 3, 4, 0])->tail(5)->last()->toArrayAssoc());
    }
    
    public function test_Flat_Flat(): void
    {
        $data = [
            [
                ['a'], ['b'],
            ], [
                ['c'], ['d'], [['foo']]
            ], [
                ['e'], ['f'],
            ],
        ];
        
        $result = Stream::from($data)->flat(1)->flat(1)->toArray();
        
        self::assertSame(['a', 'b', 'c', 'd', ['foo'], 'e', 'f'], $result);
    }
    
    public function test_FeedMany(): void
    {
        $s1 = Stream::empty()->skip(0)->limit(1)->collect();
        $s2 = Stream::empty()->skip(1)->limit(1)->collect();
        $s3 = Stream::empty()->skip(2)->limit(1)->collect();
        
        Stream::from(['a', 'b', 'c'])->feed($s1)->feed($s2)->feed($s3)->run();
        
        self::assertSame([['a'], [1 => 'b'], [2 => 'c']], [$s1->get(), $s2->get(), $s3->get()]);
    }
    
    public function test_IsEmpty(): void
    {
        $result = Stream::from([8])
            ->sort()
            ->best(10)
            ->unique()
            ->makeTuple()
            ->mapKV(static fn(array $tupple): array => [$tupple[0] => $tupple[1]])
            ->tail(5)
            ->shuffle()
            ->scan(0, Reducers::sum())
            ->reverse()
            ->map(static fn($v) => $v)
            ->reindex()
            ->moveTo('foo')
            ->mapField('foo', Mappers::shuffle())
            ->mapKey(Mappers::simple(5))
            ->mapKV(static fn($v, $k): array => [$k => $v])
            ->mapWhen('is_int', static fn($v) => $v)
            ->flip()
            ->flat()
            ->omitReps()
            ->flip()
            ->gather()
            ->chunk(3)
            ->moveTo('foo')
            ->mapField('foo', Mappers::simple(8))
            ->chunkBy(Discriminators::byField('foo'))
            ->classify(Discriminators::alternately(['foo']))
            ->map(Mappers::simple(8))
            ->categorize(Discriminators::evenOdd())
            ->segregate(3)
            ->isEmpty()
            ->get();
        
        self::assertFalse($result);
    }
    
    public function test_MapWhen_1(): void
    {
        $result = Stream::from(['a', 1, 'b', 2])
            ->mapWhen('is_string', 'strtoupper', static fn(int $num): int => $num * 2)
            ->toArrayAssoc();
            
        self::assertSame(['A', 2, 'B', 4], $result);
    }
    
    public function test_MapWhen_2(): void
    {
        $mapper = static fn(int $num): int => $num * 2;
        
        $result = Stream::from([1, 2, 3, 4])
            ->mapWhen(Filters::lessOrEqual(2), $mapper, $mapper)
            ->toArrayAssoc();
            
        self::assertSame([2, 4, 6, 8], $result);
    }
    
    public function test_MapWhen_3(): void
    {
        $mapper = static fn(int $num): int => $num * 2;
        
        $result = Stream::from([1, 2, 3, 4])
            ->mapWhen(Filters::lessOrEqual(2), $mapper, Mappers::value())
            ->toArrayAssoc();
            
        self::assertSame([2, 4, 3, 4], $result);
    }
    
    public function test_MapWhen_4(): void
    {
        $result = Stream::from([1, 2, 3, 4])
            ->mapWhen(Filters::lessOrEqual(2), Mappers::value(), Mappers::value())
            ->toArrayAssoc();
            
        self::assertSame([1, 2, 3, 4], $result);
    }
    
    public function test_MapFieldWhen_normal(): void
    {
        $rowset = [
            ['foo' => 3, 'bar' => 'd'],
            ['foo' => null, 'bar' => 'o'],
            ['foo' => 2, 'bar' => 'n'],
        ];
        
        $mapper = Mappers::getAdapter('strtoupper');
        
        $stream = Stream::from($rowset)
            ->mapFieldWhen('bar', 'is_string', $mapper)
            ->collect();
        
        self::assertSame([
            ['foo' => 3, 'bar' => 'D'],
            ['foo' => null, 'bar' => 'O'],
            ['foo' => 2, 'bar' => 'N'],
        ], $stream->toArray());
    }
    
    public function test_MapFieldWhen_barren(): void
    {
        $rowset = [
            ['foo' => 3, 'bar' => 'd'],
            ['foo' => null, 'bar' => 'o'],
            ['foo' => 2, 'bar' => 'n'],
        ];
        
        $mapper = Mappers::getAdapter('strtoupper');
        
        $stream = Stream::from($rowset)
            ->mapFieldWhen('bar', 'is_string', $mapper, $mapper)
            ->collect();
        
        self::assertSame([
            ['foo' => 3, 'bar' => 'D'],
            ['foo' => null, 'bar' => 'O'],
            ['foo' => 2, 'bar' => 'N'],
        ], $stream->toArray());
    }
    
    public function test_Sort_Find(): void
    {
        self::assertSame([3 => 'c'], Stream::from(['b', 'a', 'd', 'c'])->sort()->find('c')->toArrayAssoc());
        self::assertSame([], Stream::from(['b', 'a', 'd', 'c'])->sort()->find('e')->toArrayAssoc());
    }
    
    public function test_Sort_Has(): void
    {
        self::assertTrue(Stream::from(['b', 'a', 'd', 'c'])->sort()->has('c')->get());
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->sort()->has('e')->get());
    }
    
    public function test_Sort_HasOnly(): void
    {
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->sort()->hasOnly(['a', 'b'])->get());
        self::assertTrue(Stream::from(['a', 'b', 'a'])->sort()->hasOnly(['a', 'b'])->get());
    }
    
    public function test_Sort_HasEvery(): void
    {
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->sort()->hasEvery(['a', 'e'])->get());
        self::assertTrue(Stream::from(['b', 'a', 'd', 'c'])->sort()->hasEvery(['a', 'c'])->get());
    }
    
    public function test_Shuffle_Find(): void
    {
        self::assertSame([3 => 'c'], Stream::from(['b', 'a', 'd', 'c'])->shuffle()->find('c')->toArrayAssoc());
        self::assertSame([], Stream::from(['b', 'a', 'd', 'c'])->shuffle()->find('e')->toArrayAssoc());
    }
    
    public function test_Shuffle_Has(): void
    {
        self::assertTrue(Stream::from(['b', 'a', 'd', 'c'])->shuffle()->has('c')->get());
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->shuffle()->has('e')->get());
    }
    
    public function test_Shuffle_HasOnly(): void
    {
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->shuffle()->hasOnly(['a', 'b'])->get());
        self::assertTrue(Stream::from(['a', 'b', 'a'])->shuffle()->hasOnly(['a', 'b'])->get());
    }
    
    public function test_Shuffle_HasEvery(): void
    {
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->shuffle()->hasEvery(['a', 'e'])->get());
        self::assertTrue(Stream::from(['b', 'a', 'd', 'c'])->shuffle()->hasEvery(['a', 'c'])->get());
    }
    
    public function test_Reverse_Find(): void
    {
        self::assertSame([3 => 'c'], Stream::from(['b', 'a', 'd', 'c'])->reverse()->find('c')->toArrayAssoc());
        self::assertSame([], Stream::from(['b', 'a', 'd', 'c'])->reverse()->find('e')->toArrayAssoc());
    }
    
    public function test_Reverse_Has(): void
    {
        self::assertTrue(Stream::from(['b', 'a', 'd', 'c'])->reverse()->has('c')->get());
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->reverse()->has('e')->get());
    }
    
    public function test_Reverse_HasOnly(): void
    {
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->reverse()->hasOnly(['a', 'b'])->get());
        self::assertTrue(Stream::from(['a', 'b', 'a'])->reverse()->hasOnly(['a', 'b'])->get());
    }
    
    public function test_Reverse_HasEvery(): void
    {
        self::assertFalse(Stream::from(['b', 'a', 'd', 'c'])->reverse()->hasEvery(['a', 'e'])->get());
        self::assertTrue(Stream::from(['b', 'a', 'd', 'c'])->reverse()->hasEvery(['a', 'c'])->get());
    }
    
    public function test_Reverse_Count(): void
    {
        self::assertSame(3, Stream::from(['b', 'a', 'c'])->reverse()->count()->get());
    }
    
    public function test_Shuffle_Count(): void
    {
        self::assertSame(3, Stream::from(['b', 'a', 'c'])->shuffle()->count()->get());
    }
    
    public function test_Sort_Count(): void
    {
        self::assertSame(3, Stream::from(['b', 'a', 'c'])->sort()->count()->get());
    }
    
    public function test_Reindex_Count(): void
    {
        self::assertSame(3, Stream::from(['b', 'a', 'c'])->reindex()->count()->get());
    }
    
    public function test_Unique(): void
    {
        self::assertSame(
            [0 => 'a', 2 => 'b', 5 => 'c'],
            Stream::from(['a', 'a', 'b', 'b', 'a', 'c', 'c', 'b', 'a'])->unique()->toArrayAssoc()
        );
    }
    
    public function test_Accumulate_Reindex_default(): void
    {
        self::assertSame(
            [['a', 'b'], [3 => 'c', 'd']],
            Stream::from(['a', 'b', 1, 'c', 'd'])->accumulate('is_string')->reindex()->toArrayAssoc()
        );
    }
    
    public function test_Accumulate_Reindex_custom(): void
    {
        self::assertSame(
            [1 => ['a', 'b'], 3 => [3 => 'c', 'd']],
            Stream::from(['a', 'b', 1, 'c', 'd'])->accumulate('is_string')->reindex(1, 2)->toArrayAssoc()
        );
    }
    
    public function test_Aggregate_Reindex_default(): void
    {
        $data = [
            ['zen' => 4, 'foo' => 3, 'bar' => 1],
            ['zen' => 2, 'foo' => 1, 'bar' => 2],
            ['zen' => 1, 'foo' => 2, 'bar' => 3],
        ];
        
        $result = Stream::from($data)->flat()->aggregate(['foo', 'bar'])->reindex()->toArrayAssoc();
        
        self::assertSame(
            [
                ['foo' => 3, 'bar' => 1],
                ['foo' => 1, 'bar' => 2],
                ['foo' => 2, 'bar' => 3],
            ],
            $result
        );
    }
    
    public function test_Aggregate_Reindex_custom(): void
    {
        $data = [
            ['zen' => 4, 'foo' => 3, 'bar' => 1],
            ['zen' => 2, 'foo' => 1, 'bar' => 2],
            ['zen' => 1, 'foo' => 2, 'bar' => 3],
        ];
        
        $result = Stream::from($data)->flat()->aggregate(['foo', 'bar'])->reindex(1, 2)->toArrayAssoc();
        
        self::assertSame(
            [
                1 => ['foo' => 3, 'bar' => 1],
                3 => ['foo' => 1, 'bar' => 2],
                5 => ['foo' => 2, 'bar' => 3],
            ],
            $result
        );
    }
    
    public function test_Chunk_Reindex_default(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e'])->chunk(2)->reindex()->toArrayAssoc();
        
        self::assertSame([
            ['a', 'b'],
            [2 => 'c', 'd'],
            [4 => 'e']
        ], $result);
    }
    
    public function test_Chunk_Reindex_custom(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e'])->chunk(2)->reindex(1, 2)->toArrayAssoc();
        
        self::assertSame([
            1 => ['a', 'b'],
            3 => [2 => 'c', 'd'],
            5 => [4 => 'e']
        ], $result);
    }
    
    public function test_Segregate_Reindex_default(): void
    {
        $result = Stream::from([3, 1, 2, 1, 2])->segregate()->reindex()->toArrayAssoc();
        
        self::assertSame([
            [1 => 1, 3 => 1],
            [2 => 2, 4 => 2],
            [0 => 3],
        ], $result);
    }
    
    public function test_Segregate_Reindex_custom(): void
    {
        $result = Stream::from([3, 1, 2, 1, 2])->segregate()->reindex(1, 2)->toArrayAssoc();
        
        self::assertSame([
            1 => [1 => 1, 3 => 1],
            3 => [2 => 2, 4 => 2],
            5 => [0 => 3],
        ], $result);
    }
    
    public function test_Tokenize_Reindex_default(): void
    {
        $result = Stream::from(['this is', 'the way'])->tokenize()->reindex()->toArrayAssoc();
        
        self::assertSame(['this', 'is', 'the', 'way'], $result);
    }
    
    public function test_Tokenize_Reindex_custom(): void
    {
        $result = Stream::from(['this is', 'the way'])->tokenize()->reindex(1, 2)->toArrayAssoc();
        
        self::assertSame([1 => 'this', 3 => 'is', 5 => 'the', 7 => 'way'], $result);
    }
    
    public function test_Tuple_Reindex_default(): void
    {
        $result = Stream::from(['a', 'b', 'c'])->makeTuple()->reindex()->toArrayAssoc();
        
        self::assertSame([
            [0, 'a'],
            [1, 'b'],
            [2, 'c'],
        ], $result);
    }
    
    public function test_Tuple_Reindex_custom(): void
    {
        $result = Stream::from(['a', 'b', 'c'])->makeTuple()->reindex(1, 2)->toArrayAssoc();
        
        self::assertSame([
            1 => [0, 'a'],
            3 => [1, 'b'],
            5 => [2, 'c'],
        ], $result);
    }
    
    public function test_Reindex_Reindex(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd'])->reindex()->reindex(1, 2)->toArrayAssoc();
        self::assertSame([1 => 'a', 3 => 'b', 5 => 'c', 7 => 'd'], $result);
        
        $result = Stream::from(['a', 'b', 'c', 'd'])->reindex(1, 2)->reindex()->toArrayAssoc();
        self::assertSame(['a', 'b', 'c', 'd'], $result);
    }
    
    public function test_Flip_Collect(): void
    {
        $data = ['a', 1, 2, 'b', 3, 'c', 4];
        
        self::assertSame($data, Stream::from(\array_flip($data))->flip()->collect()->toArray());
    }
    
    public function test_Flip_CollectKeys(): void
    {
        $data = ['a', 1, 2, 'b', 3, 'c', '4'];
        
        self::assertSame($data, Stream::from($data)->flip()->collectKeys()->toArray());
    }
    
    /**
     * @dataProvider getDataForTestReindexAccumulate
     */
    public function test_Reindex_Accumulate(int $start, int $step, bool $reindex, array $expected): void
    {
        $data = [5 => 'a', 'b', 1, 'a', 'c', 2, 'b'];
        
        $result = Stream::from($data)->reindex($start, $step)->accumulate('is_string', $reindex)->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataForTestReindexAccumulate(): array
    {
        $normal = [
            [0 => 'a', 1 => 'b'],
            [3 => 'a', 4 => 'c'],
            [6 => 'b'],
        ];
        
        $reindexed = [
            [0 => 'a', 1 => 'b'],
            [0 => 'a', 1 => 'c'],
            [0 => 'b'],
        ];
        
        $custom = [
            [1 => 'a', 3 => 'b'],
            [7 => 'a', 9 => 'c'],
            [13 => 'b'],
        ];
        
        return $this->buildReindexReindexableTestData($normal, $reindexed, $custom);
    }
    
    /**
     * @dataProvider getDataForTestReindexChunk
     */
    public function test_Reindex_Chunk(int $start, int $step, bool $reindex, array $expected): void
    {
        $data = [5 => 'a', 'b', 1, 'a', 'c', 2, 'b'];
        
        $result = Stream::from($data)->reindex($start, $step)->chunk(3, $reindex)->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataForTestReindexChunk(): array
    {
        $normal = [
            [0 => 'a', 'b', 1],
            [3 => 'a', 'c', 2],
            [6 => 'b'],
        ];
        
        $reindexed = [
            [0 => 'a', 'b', 1],
            [0 => 'a', 'c', 2],
            [0 => 'b'],
        ];
        
        $custom = [
            [1 => 'a', 3 => 'b', 5 => 1],
            [7 => 'a', 9 => 'c', 11 => 2],
            [13 => 'b'],
        ];
        
        return $this->buildReindexReindexableTestData($normal, $reindexed, $custom);
    }
    
    /**
     * @dataProvider getDataAForTestReindexChunkBy
     */
    public function test_Reindex_ChunkBy(int $start, int $step, bool $reindex, array $expected): void
    {
        $result = Stream::from([5 => 'a', 'b', 1, 'a', 'c', 2, 'b'])
            ->reindex($start, $step)
            ->chunkBy('is_string', $reindex)
            ->reindex()
            ->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataAForTestReindexChunkBy(): array
    {
        $normal = [
            [0 => 'a', 'b'],
            [2 => 1],
            [3 => 'a', 'c'],
            [5 => 2],
            [6 => 'b'],
        ];
        
        $reindexed = [
            [0 => 'a', 'b'],
            [0 => 1],
            [0 => 'a', 'c'],
            [0 => 2],
            [0 => 'b'],
        ];
        
        $custom = [
            [1 => 'a', 3 => 'b'],
            [5 => 1],
            [7 => 'a', 9 => 'c'],
            [11 => 2],
            [13 => 'b'],
        ];
        
        return $this->buildReindexReindexableTestData($normal, $reindexed, $custom);
    }
    
    /**
     * @dataProvider getDataAForTestReindexCollect
     */
    public function test_Reindex_Collect(int $start, int $step, bool $reindex, array $expected): void
    {
        $result = Stream::from([5 => 'a', 'b', 'c', 'a'])
            ->reindex($start, $step)
            ->collect($reindex)
            ->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataAForTestReindexCollect(): array
    {
        $normal = [0 => 'a', 'b', 'c', 'a'];
        $reindexed = [0 => 'a', 'b', 'c', 'a'];
        $custom = [1 => 'a', 3 => 'b', 5 => 'c', 7 => 'a'];
        
        return $this->buildReindexReindexableTestData($normal, $reindexed, $custom);
    }
    
    /**
     * @dataProvider getDataForTestReindexGather
     */
    public function test_Reindex_Gather(int $start, int $step, bool $reindex, array $expected): void
    {
        self::assertSame(
            $expected,
            Stream::from([2 => 'a', 'b', 'c'])->reindex($start, $step)->gather($reindex)->toArray()
        );
    }
    
    public function getDataForTestReindexGather(): array
    {
        return [
            //start, step, reindex, expected
            [0, 1, false, [['a', 'b', 'c']]],
            [0, 1, true, [['a', 'b', 'c']]],
            [1, 1, false, [[1 => 'a', 'b', 'c']]],
            [1, 2, false, [[1 => 'a', 3 => 'b', 5 => 'c']]],
            [1, 2, true, [['a', 'b', 'c']]],
        ];
    }
    
    /**
     * @dataProvider getDataForTestReindexSegregate
     */
    public function test_Reindex_Segregate(int $start, int $step, bool $reindex, array $expected): void
    {
        $result = Stream::from([5 => 'a', 'b', 'a', 'c', 'b'])
            ->reindex($start, $step)
            ->segregate(null, null, Check::VALUE, $reindex)
            ->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataForTestReindexSegregate(): array
    {
        $normal = [
            [0 => 'a', 2 => 'a'],
            [1 => 'b', 4 => 'b'],
            [3 => 'c'],
        ];
        
        $reindexed = [
            [0 => 'a', 1 => 'a'],
            [0 => 'b', 1 => 'b'],
            [0 => 'c'],
        ];
        
        $custom = [
            [1 => 'a', 5 => 'a'],
            [3 => 'b', 9 => 'b'],
            [7 => 'c'],
        ];
        
        return $this->buildReindexReindexableTestData($normal, $reindexed, $custom);
    }
    
    /**
     * @dataProvider getDataForTestReindexUptrends
     */
    public function test_Reindex_Uptrends(int $start, int $step, bool $reindex, array $expected): void
    {
        $result = Stream::from([5 => 'a', 'b', 'a', 'b', 'c', 'b', 'a', 'c'])
            ->reindex($start, $step)
            ->accumulateUptrends(null, $reindex)
            ->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function getDataForTestReindexUptrends(): array
    {
        $normal = [
            [0 => 'a', 'b'],
            [2 => 'a', 'b', 'c'],
            [6 => 'a', 'c'],
        ];
        
        $reindexed = [
            [0 => 'a', 'b'],
            [0 => 'a', 'b', 'c'],
            [0 => 'a', 'c'],
        ];
        
        $custom = [
            [1 => 'a', 3 => 'b'],
            [5 => 'a', 7 => 'b', 9 => 'c'],
            [13 => 'a', 15 => 'c'],
        ];
        
        return $this->buildReindexReindexableTestData($normal, $reindexed, $custom);
    }
    
    public function test_Reindex_UnpackTuple(): void
    {
        $result = Stream::from([['a', 1], ['b', 2], ['c', 3]])
            ->reindex(3, 6)
            ->unpackTuple()
            ->toArrayAssoc();
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $result);
    }
    
    public function test_MapKey_UnpackTuple(): void
    {
        $result = Stream::from([['a', 1], ['b', 2], ['c', 3]])
            ->mapKey(Mappers::simple(1))
            ->unpackTuple()
            ->toArrayAssoc();
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $result);
    }
    
    public function test_MakeTuple_UnpackTuple_the_same(): void
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        
        $result = Stream::from($data)->makeTuple()->unpackTuple()->toArrayAssoc();
        
        self::assertSame($data, $result);
    }
    
    public function test_UnpackTuple_MakeTuple_the_same(): void
    {
        $data = [['a', 1], ['b', 2], ['c', 3]];
        
        $result = Stream::from($data)->unpackTuple()->makeTuple()->toArrayAssoc();
        
        self::assertSame($data, $result);
    }
    
    private function buildReindexReindexableTestData(array $normal, array $reindexed, array $custom): array
    {
        return [
            'default_default' => [0, 1, false, $normal],
            'default_reindex' => [0, 1, true, $reindexed],
            'custom_default' => [1, 2, false, $custom],
            'custom_reindex' => [1, 2, true, $reindexed],
        ];
    }
}