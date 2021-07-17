<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Predicate\Predicates;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\StreamMaker;
use PHPUnit\Framework\TestCase;

final class StreamTest extends TestCase
{
    public function test_toArray_numerical_by_default(): void
    {
        self::assertSame([1, 2, 3], Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->toArray());
    }
    
    public function test_toArray_preserve_keys(): void
    {
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->toArray(true));
    }
    
    public function test_toArrayAssoc(): void
    {
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->toArrayAssoc());
    }
    
    public function test_toArray_empty_stream(): void
    {
        self::assertSame([], Stream::empty()->toArray());
    }
    
    public function test_toString_default(): void
    {
        self::assertSame('1,2,3', Stream::from([1, 2, 3])->toString());
    }
    
    public function test_toString_custom_separator(): void
    {
        self::assertSame('1|2|3', Stream::from([1, 2, 3])->toString('|'));
    }
    
    public function test_toString_empty_string(): void
    {
        self::assertSame('', Stream::empty()->toString());
    }
    
    public function test_toJson_default(): void
    {
        self::assertSame('[1,"2"]', Stream::from(['a' => 1, '2'])->toJson());
    }
    
    public function test_toJson_with_flags(): void
    {
        self::assertSame('[1,2]', Stream::from([1, 2])->toJson(\JSON_NUMERIC_CHECK, true));
    }
    
    public function test_toJson_preserve_keys(): void
    {
        self::assertSame('{"a":1,"b":2}', Stream::from(['a' => 1, 'b' => 2])->toJson(0, true));
    }
    
    public function test_toJsonAssoc(): void
    {
        self::assertSame('{"a":1,"b":2}', Stream::from(['a' => 1, 'b' => 2])->toJsonAssoc());
    }
    
    public function test_toJson_drop_keys(): void
    {
        self::assertSame('[1,2]', Stream::from(['a' => 1, 'b' => 2])->toJson(0, false));
    }
    
    public function test_limit(): void
    {
        self::assertSame([1, 2], Stream::from([1, 2, 3, 4])->limit(2)->toArray());
    }
    
    public function test_skip(): void
    {
        self::assertSame([3, 4], Stream::from([1, 2, 3, 4])->skip(2)->toArray());
    }
    
    public function test_filter_notNull(): void
    {
        self::assertSame([1, 2], Stream::from([1, null, 2])->notNull()->toArray());
    }
    
    public function test_filter_noEmpty(): void
    {
        self::assertSame([1, 2, 3], Stream::from(['', 1, 0, 2, false, 3, []])->notEmpty()->toArray());
    }
    
    public function test_filter_without_default(): void
    {
        self::assertSame([2, 3], Stream::from([1, 2, 3, 4])->without([0, 1, 4])->toArray());
    }
    
    public function test_filter_without_variant(): void
    {
        $a = ['value' => 15];
        $b = ['value' => 3];
        $c = ['value' => 9];
        
        self::assertSame([$b], Stream::from([$a, $b, $c])->without([$a, $c])->toArray());
    }
    
    public function test_filter_only_default(): void
    {
        self::assertSame([2, 3], Stream::from([1, 2, 3, 4])->only([0, 2, 3])->toArray());
    }
    
    public function test_filter_only_variant(): void
    {
        $a = ['value' => 15];
        $b = ['value' => 3];
        $c = ['value' => 9];
        $d = ['value' => 10];
    
        self::assertSame([$b], Stream::from([$a, $b, $c])->only([$b, $d])->toArray());
    }
    
    public function test_filter_only_check_keys(): void
    {
        $input = ['a' => 5, 'b' => 2, 'c' => 4, 'd' => 6, 'e' => 3];
        self::assertSame([2, 6], Stream::from($input)->only(['b', 'd', 'f'], Check::KEY)->toArray());
    }
    
    public function test_filter_only_check_any(): void
    {
        $input = ['a' => 5, 'b' => 2, 'c' => 4, 'd' => 6, 'e' => 3];
        self::assertSame([5, 6], Stream::from($input)->only(['d', 'f', 5], Check::ANY)->toArray());
    }
    
    public function test_filter_only_check_both(): void
    {
        $input = ['a' => 5, 'b' => 2, 'c' => 4, 'd' => 6, 'e' => 3];
        self::assertSame([5], Stream::from($input)->only(['a', 'd', 5, 2], Check::BOTH)->toArray());
    }
    
    public function test_filter_greaterThan(): void
    {
        self::assertSame([7, 5], Stream::from([2, 7, 1, 5, 4, 3])->greaterThan(4)->toArray());
    }
    
    public function test_filter_greaterOrEqual(): void
    {
        self::assertSame([7, 5, 4], Stream::from([2, 7, 1, 5, 4, 3])->greaterOrEqual(4)->toArray());
    }
    
    public function test_filter_lessThan(): void
    {
        self::assertSame([2, 1, 3], Stream::from([2, 7, 1, 5, 4, 3])->lessThan(4)->toArray());
    }
    
    public function test_filter_lessOrEqual(): void
    {
        self::assertSame([2, 1, 4, 3], Stream::from([2, 7, 1, 5, 4, 3])->lessOrEqual(4)->toArray());
    }
    
    public function test_filter_onlyNumeric(): void
    {
        self::assertSame([3, '5', 14.0], Stream::from(['a', 3, false, '5', [], 14.0])->onlyNumeric()->toArray());
    }
    
    public function test_filter_onlyIntegers(): void
    {
        self::assertSame([3], Stream::from(['a', 3, false, '5', [], 14.0])->onlyIntegers()->toArray());
    }
    
    public function test_filter_onlyStrings(): void
    {
        self::assertSame(['a', '5'], Stream::from(['a', 3, false, '5', [], 14.0])->onlyStrings()->toArray());
    }
    
    public function test_filter_can_accept_name_of_function(): void
    {
        self::assertSame([3], Stream::from(['a', 3, false, '5', [], 14.0])->filter('is_int')->toArray());
    }
    
    public function test_filter_can_accept_callable(): void
    {
        $filter = function ($value) {
            return \is_float($value);
        };
        
        self::assertSame([14.0], Stream::from(['a', 3, false, '5', [], 14.0])->filter($filter)->toArray());
    }
    
    public function test_filter_can_accept_Filter_instance(): void
    {
        $filter = new class implements Filter {
            public function isAllowed($value, $key, int $mode = Check::VALUE): bool {
                return \is_bool($value);
            }
        };
    
        self::assertSame([false], Stream::from(['a', 3, false, '5', [], 14.0])->filter($filter)->toArray());
    }
    
    public function test_omit_can_accept_name_of_function(): void
    {
        self::assertSame(
            [3, false, [], 14.0],
            Stream::from(['a', 3, false, '5', [], 14.0])->omit('is_string')->toArray()
        );
    }
    
    public function test_omit_can_accept_callable(): void
    {
        $filter = function ($value) {
            return \is_scalar($value);
        };
        
        self::assertSame([[]], Stream::from(['a', 3, false, '5', [], 14.0])->omit($filter)->toArray());
    }
    
    public function test_omit_can_accept_Filter_instance(): void
    {
        $filter = new class implements Filter {
            public function isAllowed($value, $key, int $mode = Check::VALUE): bool {
                return \is_bool($value) || \is_array($value);
            }
        };
    
        self::assertSame(
            ['a', 3, '5', 14.0],
            Stream::from(['a', 3, false, '5', [], 14.0])->omit($filter)->toArray()
        );
    }
    
    public function test_omit_can_check_keys(): void
    {
        self::assertSame(
            ['a', false, '5', 14.0],
            Stream::from(['a', 'k1' => 3, false, '5', 'k2' => [], 14.0])->omit('is_string', Check::KEY)->toArray()
        );
    }
    
    public function test_omit_can_check_any(): void
    {
        self::assertSame(
            [false, 14.0],
            Stream::from(['a', 'k1' => 3, false, '5', 'k2' => [], 14.0])->omit('is_string', Check::ANY)->toArray()
        );
    }
    
    public function test_omit_can_check_both(): void
    {
        $stream = Stream::from(['a', 'k1' => 3, false, 'u' => '5', 'k2' => [], 14.0])->omit('is_string', Check::BOTH);
        self::assertSame(['a', 'k1' => 3, false, 'k2' => [], 14.0], $stream->toArrayAssoc());
    }
    
    public function test_castToInt(): void
    {
        self::assertSame([3, 0, 5, 14], Stream::from([3, false, '5', 14.0])->castToInt()->toArray());
    }
    
    public function test_cast_field_in_row_using_callable(): void
    {
        $rowset = [
            ['id' => '1', 'name' => 'Joe'],
            ['id' => '2', 'name' => 'Bill'],
        ];
        
        $expected = [
            ['id' => 1, 'name' => 'Joe'],
            ['id' => 2, 'name' => 'Bill'],
        ];
        
        self::assertSame($expected, Stream::from($rowset)
            ->map(function (array $row) {
                $row['id'] = (int) $row['id'];
                return $row;
            })
            ->toArray()
        );
    }
    
    public function test_cast_field_in_row_using_mapper(): void
    {
        $rowset = [
            ['id' => '1', 'name' => 'Joe'],
            ['id' => '2', 'name' => 'Bill'],
        ];
    
        $expected = [
            ['id' => 1, 'name' => 'Joe'],
            ['id' => 2, 'name' => 'Bill'],
        ];
        
        self::assertSame($expected, Stream::from($rowset)->map(Mappers::toInt('id'))->toArray());
    }
    
    public function test_cast_field_in_row_using_method(): void
    {
        $rowset = [
            ['id' => '1', 'name' => 'Joe'],
            ['id' => '2', 'name' => 'Bill'],
        ];
    
        $expected = [
            ['id' => 1, 'name' => 'Joe'],
            ['id' => 2, 'name' => 'Bill'],
        ];
        
        self::assertSame($expected, Stream::from($rowset)->castToInt('id')->toArray());
    }
    
    public function test_map_can_accept_name_of_function(): void
    {
        self::assertSame([3, 1, 2, 4], Stream::from(['ccc', 'a', 'bb', 'dddd'])->map('strlen')->toArray());
    }
    
    public function test_map_can_accept_callable(): void
    {
        $mapper = function ($value, $key) {
            return $key.':'.$value;
        };
        
        self::assertSame(['0:a', '1:b', '2:c'], Stream::from(['a', 'b', 'c'])->map($mapper)->toArray());
    }
    
    public function test_map_can_accept_Mapper_isntance(): void
    {
        $mapper = new class implements Mapper {
            public function map($value, $key) {
                return 2 * $value;
            }
        };
        
        self::assertSame([2, 4, 6], Stream::from([1, 2, 3])->map($mapper)->toArray());
    }
    
    public function test_collectIn_not_preserving_keys(): void
    {
        //given
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $stream = Stream::from($array);
        $buffer = new \ArrayObject();
        
        //when
        $stream->collectIn($buffer)->run();
        
        //then
        self::assertSame(\array_values($array), $buffer->getArrayCopy());
    }
    
    public function test_collectIn_with_preserving_keys(): void
    {
        //given
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $stream = Stream::from($array);
        $buffer = new \ArrayObject();
        
        //when
        $stream->collectIn($buffer, true)->run();
        
        //then
        self::assertSame($array, $buffer->getArrayCopy());
    }
    
    public function test_call_can_accept_callable(): void
    {
        //given
        $data = [];
        $consumer = static function ($value, $key) use (&$data) {
            $data[] = ['key' => $key, 'value' => $value];
        };
    
        $array = ['a' => 1, 'b' => 2];
        $stream = Stream::from($array)->call($consumer);
        
        //when
        $stream->run();
        
        //then
        self::assertSame([
            ['key' => 'a', 'value' => 1],
            ['key' => 'b', 'value' => 2],
        ], $data);
    }
    
    public function test_call_can_accept_consumer(): void
    {
        //given
        $consumer = new class implements Consumer {
            public array $data = [];
            
            public function consume($value, $key): void {
                $this->data[] = ['key' => $key, 'value' => $value];
            }
        };
        
        $array = ['a' => 1, 'b' => 2];
        $stream = Stream::from($array)->call($consumer);
        
        //when
        $stream->run();
        
        //then
        self::assertSame([
            ['key' => 'a', 'value' => 1],
            ['key' => 'b', 'value' => 2],
        ], $consumer->data);
    }
    
    public function test_join_can_accept_another_source_of_data_for_stream(): void
    {
        self::assertSame(['a', 'b', 'x', 'y'], Stream::from(['a', 'b'])->join(['x', 'y'])->toArray());
    }
    
    public function test_unique_values(): void
    {
        self::assertSame([6, 3, 8], Stream::from([6, 3, 6, 8, 3])->unique()->toArray());
    }
    
    public function test_unique_keys(): void
    {
        //keys in array are always unique, so this is the hack to produce non-unique keys for stream from $producer
        $keys = ['a', 'b', 'a', 'c', 'a'];
        $producer = Stream::from([0, 1, 2, 3, 4])->mapKey(static fn(int $value) => $keys[$value]);
        
        self::assertSame([0, 1, 3], Stream::from($producer)->unique(null, Check::KEY)->toArray());
    }
    
    public function test_unique_any(): void
    {
        $inputData = [0 => 'a', 1 => 3, 'b' => 2, 2 => 'a', 3 => 'b', 4 => 'b'];
        $actual = Stream::from($inputData)->unique(null, Check::ANY)->toArrayAssoc();
        
        self::assertSame([0 => 'a', 1 => 3, 'b' => 2, 4 => 'b'], $actual);
    }
    
    public function test_unique_both(): void
    {
        $inputData = [0 => 'a', 1 => 'a', 'b' => 1, 2 => 'a', 3 => 'c', 4 => 'b'];
        $actual = Stream::from($inputData)->unique(null, Check::BOTH)->toArrayAssoc();
        
        self::assertSame([0 => 'a', 'b' => 1, 3 => 'c'], $actual);
    }
    
    public function test_sort_values(): void
    {
        self::assertSame([1, 2, 3, 4, 5], Stream::from([3, 5, 1, 4, 2])->sort()->toArray());
    }
    
    public function test_sort_keys(): void
    {
        self::assertSame(
            ['a' => 2, 'z' => 1],
            Stream::from(['z' => 1, 'a' => 2])->sort(null, Check::KEY)->toArray(true)
        );
    }
    
    public function test_rsort_values(): void
    {
        self::assertSame([5, 4, 3, 2, 1], Stream::from([3, 5, 1, 4, 2])->rsort()->toArray());
    }
    
    public function test_rsort_keys(): void
    {
        self::assertSame(
            ['z' => 1, 'a' => 2],
            Stream::from(['z' => 1, 'a' => 2])->rsort(null, Check::KEY)->toArray(true)
        );
    }
    
    public function test_reverse(): void
    {
        self::assertSame([4, 3, 2, 1], Stream::from([1, 2, 3, 4])->reverse()->toArray());
    }
    
    public function test_shuffle(): void
    {
        $arr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27];
        
        for ($i = 0; $i < 10; ++$i) {
            $result = Stream::from($arr)->shuffle()->toArray();
            if ($result !== $arr) {
                break;
            }
        }
    
        if ($i === 10) {
            self::fail('shuffle doesny work!');
        }
        
        self::assertCount(\count($arr), $result);
    
        foreach ($arr as $v) {
            self::assertContains($v, $arr);
        }
    }
    
    public function test_reindex(): void
    {
        self::assertSame([1,2,3], Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->reindex()->toArray(true));
    }
    
    public function test_flip(): void
    {
        self::assertSame(['a' => 0, 'b' => 1, 'c' => 2], Stream::from(['a', 'b', 'c'])->flip()->toArrayAssoc());
    }
    
    public function test_count(): void
    {
        self::assertSame(4, Stream::from(['a', 'b', 'c', 'd'])->count()->get());
    }
    
    public function test_reduce_can_accept_name_of_function(): void
    {
        self::assertSame(2, Stream::from([5, 2, 7])->reduce('min')->get());
    }
    
    public function test_reduce_can_accept_callable(): void
    {
        $reducer = static function ($acc, $value) {
            return $acc + $value;
        };
        
        self::assertSame(14, Stream::from([5, 2, 7])->reduce($reducer)->get());
    }
    
    public function test_reduce_can_accept_Reducer_instance(): void
    {
        self::assertSame(5, Stream::from([2, 4, 6, 8])->reduce(Reducers::average())->get());
    }
    
    public function test_stream_isEmpty(): void
    {
        self::assertTrue(Stream::empty()->isEmpty()->get());
        self::assertFalse(Stream::from(['a'])->isEmpty()->get());
    }
    
    public function test_stream_isNotEmpty(): void
    {
        self::assertTrue(Stream::from(['a'])->isNotEmpty()->get());
        self::assertFalse(Stream::empty()->isNotEmpty()->get());
    }
    
    public function test_get_first_value(): void
    {
        self::assertSame(4, Stream::from([4, 5])->first('a')->get());
    }
    
    public function test_get_first_key(): void
    {
        self::assertSame(0, Stream::from([4, 5])->first('a')->key());
    }
    
    public function test_get_default_first_element_if_stream_empty(): void
    {
        self::assertSame('a', Stream::empty()->first('a')->get());
    }
    
    public function test_get_first_element(): void
    {
        self::assertSame([0, 4], Stream::from([4, 5])->first('a')->tuple());
    }
    
    public function test_get_last_value(): void
    {
        self::assertSame(5, Stream::from([4, 5])->last('a')->get());
    }
    
    public function test_get_last_key(): void
    {
        self::assertSame(1, Stream::from([4, 5])->last('a')->key());
    }
    
    public function test_get_last_element(): void
    {
        self::assertSame([1, 5], Stream::from([4, 5])->last('a')->tuple());
    }
    
    public function test_get_default_last_element_if_stream_empty(): void
    {
        self::assertSame('a', Stream::empty()->last('a')->get());
    }
    
    public function test_forEach(): void
    {
        //given
        $data = [];
        $consumer = static function ($value, $key) use (&$data) {
            $data[] = $key.':'.$value;
        };
        
        $stream = Stream::from(['a', 'b', 'c']);
        
        //when
        $stream->forEach($consumer);
    
        //then
        self::assertSame(['0:a', '1:b', '2:c'], $data);
    }
    
    public function test_stream_can_be_iterable_as_array(): void
    {
        $stream = Stream::from(['a', 'b', 'c']);
        
        $data = [];
        foreach ($stream as $value) {
            $data[] = $value;
        }
        
        self::assertSame(['a', 'b', 'c'], $data);
    }
    
    public function test_mapKey(): void
    {
        self::assertSame(
            [5 => 'a', 6 => 'b'],
            Stream::from(['a', 'b'])->mapKey(static fn($value, $key) => $key + 5)->toArray(true)
        );
    }
    
    public function test_has_can_accept_any_value(): void
    {
        self::assertFalse(Stream::from(['1', '2', '3'])->has('4')->get());
        self::assertTrue(Stream::from(['1', '2', '3'])->has('2')->get());
    }
    
    public function test_has_can_accept_name_of_function(): void
    {
        self::assertFalse(Stream::from(['1', '2', '3'])->has('is_int')->get());
        self::assertTrue(Stream::from(['1', 2, '3'])->has('is_int')->get());
    }
    
    public function test_has_can_accept_callable(): void
    {
        $predicate = static fn($v) => \is_int($v);
        
        self::assertFalse(Stream::from(['1', '2', '3'])->has($predicate)->get());
        self::assertTrue(Stream::from(['1', 2, '3'])->has($predicate)->get());
    }
    
    public function test_has_can_accept_Predicate_instance(): void
    {
        $predicate = Predicates::value(2);
        
        self::assertFalse(Stream::from(['1', '2', '3'])->has($predicate)->get());
        self::assertTrue(Stream::from(['1', 2, '3'])->has($predicate)->get());
    }
    
    public function test_hasAny(): void
    {
        self::assertFalse(Stream::from(['1', '2', '3'])->hasAny([1, 2, 3])->get());
        self::assertTrue(Stream::from(['1', 2, '3'])->hasAny([1, 2, 3])->get());
    }
    
    public function test_hasEvery(): void
    {
        self::assertFalse(Stream::from(['1', '2', '3'])->hasEvery(['1', '5'])->get());
        self::assertTrue(Stream::from(['1', 2, '3'])->hasEvery(['1', '3'])->get());
    }
    
    public function test_hasOnly(): void
    {
        self::assertFalse(Stream::from([3, 1, 2, 1, 3, 2, 4, 1, 2, 3])->hasOnly([1, 2, 3])->get());
        self::assertTrue(Stream::from([3, 1, 2, 1, 3, 2, 1, 2, 3])->hasOnly([1, 2, 3])->get());
    }
    
    public function test_has_key(): void
    {
        self::assertFalse(Stream::from(['a', 'b', 'c'])->has(4, Check::KEY)->get());
        self::assertTrue(Stream::from(['a', 'b', 'c'])->has(2, Check::KEY)->get());
    }
    
    public function test_hasAny_key(): void
    {
        self::assertFalse(Stream::from(['1', '2', '3'])->hasAny([3, 4], Check::KEY)->get());
        self::assertTrue(Stream::from(['1', 2, '3'])->hasAny([1, 2], Check::KEY)->get());
    }
    
    public function test_hasEvery_key(): void
    {
        self::assertFalse(Stream::from(['1', '2', '3'])->hasEvery([1, 3], Check::KEY)->get());
        self::assertTrue(Stream::from(['1', 2, '3'])->hasAny([1, 2], Check::KEY)->get());
    }
    
    public function test_hasOnly_key(): void
    {
        self::assertFalse(Stream::from([3, 1, 2, 1,])->hasOnly([0, 1, 2], Check::KEY)->get());
        self::assertTrue(Stream::from([3, 1, 2])->hasOnly([0, 1, 2], Check::KEY)->get());
    }
    
    public function test_collectKeys(): void
    {
        //given
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $stream = Stream::from($array);
        $buffer = new \ArrayObject();
    
        //when
        $stream->collectKeys($buffer)->run();
    
        //then
        self::assertSame(\array_keys($array), $buffer->getArrayCopy());
    }
    
    public function test_find_value(): void
    {
        $item = Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->find(2);
    
        self::assertTrue($item->found());
        self::assertSame('b', $item->key());
        self::assertSame(2, $item->get());
    }
    
    public function test_find_key(): void
    {
        $item = Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->find('b', Check::KEY);
    
        self::assertTrue($item->found());
        self::assertSame('b', $item->key());
        self::assertSame(2, $item->get());
    }
    
    public function test_find_can_accept_name_of_function(): void
    {
        $item = Stream::from(['a' => '1', 'b' => 2, 'c' => '3'])->find('is_int', Check::ANY);
    
        self::assertTrue($item->found());
        self::assertSame('b', $item->key());
        self::assertSame(2, $item->get());
    }
    
    public function test_find_can_check_both(): void
    {
        $item = Stream::from(['a' => '1', 'b' => 2, 6, 'c' => '3'])->find('is_int', Check::BOTH);
    
        self::assertTrue($item->found());
        self::assertSame(0, $item->key());
        self::assertSame(6, $item->get());
    }
    
    public function test_find_can_accept_callable(): void
    {
        $predicate = static fn($v, $k) => \is_string($k) && \is_int($v);
        $item = Stream::from(['a' => '1', 'b' => 2, 6, 'c' => '3'])->find($predicate);
    
        self::assertTrue($item->found());
        self::assertSame('b', $item->key());
        self::assertSame(2, $item->get());
    }
    
    public function test_fold_can_accept_callable(): void
    {
        $reducer = static fn($acc, $val) => $acc - $val;
        self::assertSame(7, Stream::from([1, 1, 1])->fold(10, $reducer)->get());
    }
    
    public function test_fold_can_accept_Reducer_instance(): void
    {
        self::assertSame(10, Stream::from([1, 1, 1])->fold(7, Reducers::sum())->get());
    }
    
    public function test_chunk_by_default_reindex_keys(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        
        $expected = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8],
        ];
        
        self::assertSame($expected, Stream::from($inputData)->chunk(3)->toArray());
    }
    
    public function test_chunk_assoc_preserve_keys(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        
        $expected = [
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['d' => 4, 'e' => 5, 'f' => 6],
            ['g' => 7, 'h' => 8],
        ];
        
        self::assertSame($expected, Stream::from($inputData)->chunkAssoc(3)->toArray());
    }
    
    public function test_scan(): void
    {
        self::assertSame([0, 1, 3, 6, 10], Stream::from([1, 2, 3, 4])->scan(0, Reducers::sum())->toArray());
    }
    
    public function test_flat_level_default(): void
    {
        $stream = Stream::from([
            ['a' => 1],
            'b',
            ['c' => ['d' => 2]]
        ]);
        
        self::assertSame('[1,"b",2]', $stream->flat()->toJson());
    }
    
    public function test_flat_level_1(): void
    {
        $stream = Stream::from([
            ['a' => 1],
            'b',
            ['c' => ['d' => 2]]
        ]);
        
        self::assertSame('[1,"b",{"d":2}]', $stream->flat(1)->toJson());
    }
    
    public function test_flatMap_default(): void
    {
        $result = Stream::from([['the'], ['quick'], ['brown'], ['fox'], ['jumps']])
            ->flatMap(static fn($x) => $x)
            ->toString();
        
        self::assertSame('the,quick,brown,fox,jumps', $result);
    }
    
    public function test_flatMap_level_default(): void
    {
        $stream = Stream::from([
            ['a' => 1],
            'b',
            ['c' => ['d' => 2]]
        ]);
    
        self::assertSame('[1,"b",2]', $stream->flatMap(static fn($x) => $x)->toJson());
    }
    
    public function test_flatMap_level_1(): void
    {
        $stream = Stream::from([
            ['a' => 1],
            'b',
            ['c' => ['d' => 2]]
        ]);
    
        self::assertSame('[1,"b",{"d":2}]', $stream->flatMap(static fn($x) => $x, 1)->toJson());
    }
    
    public function test_map_with_extract(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 8, 'name' => 'Joanna', 'age' => 18],
        ];
    
        $json = Stream::from($rowset)
            ->map(Mappers::extract(['name', 'age']))
            ->toJson();
        
        self::assertSame('[{"name":"Kate","age":35},{"name":"Chris","age":26},{"name":"Joanna","age":18}]', $json);
    }
    
    public function test_extract(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 8, 'name' => 'Joanna', 'age' => 18],
        ];
    
        $json = Stream::from($rowset)
            ->extract(['name', 'age'])
            ->toJson();
        
        self::assertSame('[{"name":"Kate","age":35},{"name":"Chris","age":26},{"name":"Joanna","age":18}]', $json);
    }
    
    public function test_map_extract_single_field_flatten(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 8, 'name' => 'Joanna', 'age' => 18],
        ];
    
        $json = Stream::from($rowset)
            ->map(Mappers::extract('name'))
            ->toJson();
    
        self::assertSame('["Kate","Chris","Joanna"]', $json);
    }
    
    public function test_extract_single_field_flatten(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 8, 'name' => 'Joanna', 'age' => 18],
        ];
    
        $json = Stream::from($rowset)
            ->extract('name')
            ->toJson();
    
        self::assertSame('["Kate","Chris","Joanna"]', $json);
    }
    
    public function test_map_extract_single_field_as_array(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 8, 'name' => 'Joanna', 'age' => 18],
        ];
    
        $json = Stream::from($rowset)
            ->map(Mappers::extract(['name']))
            ->toJson();
    
        self::assertSame('[{"name":"Kate"},{"name":"Chris"},{"name":"Joanna"}]', $json);
    }
    
    public function test_extract_single_field_as_array(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 8, 'name' => 'Joanna', 'age' => 18],
        ];
    
        $json = Stream::from($rowset)
            ->extract(['name'])
            ->toJson();
    
        self::assertSame('[{"name":"Kate"},{"name":"Chris"},{"name":"Joanna"}]', $json);
    }
    
    public function test_split(): void
    {
        $result = Stream::from(['the quick brown fox', 'jumps over the lazy dog'])
            ->split()
            ->toJson();
        
        self::assertSame('[["the","quick","brown","fox"],["jumps","over","the","lazy","dog"]]', $result);
    }
    
    public function test_map_split(): void
    {
        $result = Stream::from(['the quick brown fox', 'jumps over the lazy dog'])
            ->map(Mappers::split())
            ->toJson();
        
        self::assertSame('[["the","quick","brown","fox"],["jumps","over","the","lazy","dog"]]', $result);
    }
    
    public function test_while(): void
    {
        $count = 0;
        $counter = function () use (&$count) {
            ++$count;
        };
        
        Stream::of('a', 'v', 3, 'z')
            ->while('is_string')
            ->call($counter)
            ->run();
        
        self::assertSame(2, $count);
    }
    
    public function test_until(): void
    {
        $count = 0;
        $counter = function () use (&$count) {
            ++$count;
        };
    
        Stream::of('a', 'v', 3, 'z')
            ->until('is_int')
            ->call($counter)
            ->run();
    
        self::assertSame(2, $count);
    }
    
    public function test_groupBy_using_Discriminator_instance(): void
    {
        $collection = Stream::from(['y', 5, 'c', 3, 'z', 8])->groupBy(new class implements Discriminator {
            public function classify($value, $key) {
                if (\is_int($value)) {
                    return $value > 5 ? 'big_numbers' : 'small_numbers';
                }
    
                return \is_string($value) ? 'strings' : 'unknown';
            }
        });
    
        self::assertSame([8], $collection->get('big_numbers')->toArray());
        self::assertSame([5, 3], $collection->get('small_numbers')->toArray());
        self::assertSame(['y', 'c', 'z'], $collection->get('strings')->toArray());
        self::assertSame([], $collection->get('unknown')->toArray());
    }
    
    public function test_groupBy_using_Filter_instance(): void
    {
        $greaterThanFive = Stream::of(8,3,6,5,0,2,8,3,1,6,8,3,2,5,9)->groupBy(Filters::greaterThan(5));
        
        self::assertSame([8,6,8,6,8,9], $greaterThanFive->get(true)->toArray());
        self::assertSame([3,5,0,2,3,1,3,2,5], $greaterThanFive->get(false)->toArray());
    }
    
    public function test_groupBy_using_callable(): void
    {
        $integers = Stream::from(['y', 5, 'c', 3, 'z', 8])->groupBy('is_int');
        
        self::assertSame([5, 3, 8], $integers->get(true)->toArray());
        self::assertSame(['y', 'c', 'z'], $integers->get(false)->toArray());
    }
    
    public function test_groupBy_with_empty_group(): void
    {
        $streams = Stream::from(['y', 5, 'c', 3, 'z', 2])->groupBy(static fn($v) => \is_int($v) && $v > 5);
        
        self::assertTrue($streams->get(true)->isEmpty()->get());
        self::assertSame(6, $streams->get(false)->count()->get());
    }
    
    public function test_groupBy_for_many_groups(): void
    {
        $grouped = Stream::from(['y', 5, 'c', 3, ['a' => 5], 'z', 8, ['b' => 3], 2])
            ->groupBy(function ($item) {
                switch (true) {
                    case \is_array($item): return 'rows';
                    case \is_int($item): return 'integers';
                    case \is_string($item): return 'strings';
                    default: return 'other';
                }
            });
        
        self::assertSame('[{"a":5},{"b":3}]', $grouped->get('rows')->toJson());
        self::assertSame('[5,3,8,2]', $grouped->get('integers')->toJson());
        self::assertSame('["y","c","z"]', $grouped->get('strings')->toJson());
        self::assertSame('[]', $grouped->get('other')->toJson());
    }
    
    public function test_cloning_is_prohibited(): void
    {
        $this->expectException(\LogicException::class);
        
        clone Stream::empty();
    }
    
    public function test_without_with_one_value(): void
    {
        self::assertSame([1, 3], Stream::from([1, 2, 3])->without([2])->toArray());
    }
    
    public function test_only_with_one_value(): void
    {
        self::assertSame([2], Stream::from([1, 2, 3])->only([2])->toArray());
    }
    
    public function test_join_many_sources(): void
    {
        self::assertSame([1, 2, 3, 4], Stream::empty()->join([1, 2])->join([3, 4])->toArray());
    }
    
    public function test_feed(): void
    {
        //given
        $buffer = new \ArrayObject();
        $stream = Stream::empty()->collectIn($buffer);
    
        //when
        Stream::from([1, 2, 3, 4])->feed($stream)->run();
    
        //then
        self::assertSame([1, 2, 3, 4], $buffer->getArrayCopy());
    }
    
    public function test_stream_cannot_be_executed_more_than_onece(): void
    {
        $this->expectException(\LogicException::class);
        
        $stream = Stream::empty();
        $stream->run();
        $stream->run();
    }
    
    public function test_unique_value_with_comparator(): void
    {
        $given = ['a','b','a','c','d','n','a'];
        $expected = ['a','b','c','d','n'];
        
        $comparator = static fn(string $first, string $second) => $first <=> $second;
        $actual = Stream::from($given)->unique($comparator)->toArray();
        
        self::assertSame($expected, $actual);
    }
    
    public function test_unique_key_with_comparator(): void
    {
        $given = [['a' => 1],['b' => 2],['a' => 3],['c' => 4],['d' => 5],['n' => 6],['a' => 7]];
        $expected = ['a','b','c','d','n'];
        
        $comparator = static fn(string $first, string $second) => $first <=> $second;
        $actual = Stream::from($given)->flat()->unique($comparator, Check::KEY)->flip()->toArray();
        
        self::assertSame($expected, $actual);
    }
    
    public function test_unique_both_key_and_value_with_comparator(): void
    {
        $given = [['a' => 1],['b' => 2],['a' => 3],['c' => 2],['d' => 5],['n' => 1],['o' => 2]];
        $expected = ['a' => 1, 'b' => 2, 'd' => 5];
    
        $comparator = static function ($v1, $v2, $k1, $k2) {
            return ($v1 <=> $v2) === 0
                || ($k1 <=> $k2) === 0
                || ($v1 <=> $k2) === 0
                || ($v2 <=> $k1) === 0 ? 0 : 1;
        };
        
        $actual = Stream::from($given)->flat()->unique($comparator, Check::BOTH)->toArrayAssoc();
        self::assertSame($expected, $actual);
    }
    
    public function test_unique_key_or_value_with_comparator(): void
    {
        $given = [['a' => 1],['b' => 2],['a' => 3],['c' => 'c'],['d' => 5],['n' => 1],['o' => 2]];
        $expected = ['a' => 3, 'b' => 2, 'c' => 'c', 'd' => 5, 'n' => 1, 'o' => 2];
    
        $comparator = static fn($v1, $v2) => $v1 <=> $v2;
        $actual = Stream::from($given)->flat()->unique($comparator, Check::ANY)->toArrayAssoc();
        
        self::assertSame($expected, $actual);
    }
    
    public function test_unique_of_array_as_value_without_comparator(): void
    {
        $result = Stream::from([[5], [7], [5], [7]])->unique()->toArray();
        
        self::assertCount(2, $result);
    }
    
    public function test_chunk_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Stream::from([1, 2])->chunk(0);
    }
    
    public function test_flat_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Stream::from([1, 2])->flat(-1);
    }
    
    public function test_limit_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Stream::from([1, 2])->limit(-1);
    }
    
    public function test_skip_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Stream::from([1, 2])->skip(-1);
    }
    
    public function test_limit_zero_prevents_execution(): void
    {
        $counter = Consumers::counter();
        Stream::from([1, 2])->limit(0)->call($counter)->run();
        
        self::assertSame(0, $counter->count());
    }
    
    public function test_sort_by_keys_with_comparator(): void
    {
        $data = ['c' => 1, 'a' => 2, 'd' => 3, 'b' => 4];
        $actual = Stream::from($data)->sort(Comparators::default(), Check::KEY)->toArrayAssoc();
        
        self::assertSame(['a' => 2, 'b' => 4, 'c' => 1, 'd' => 3], $actual);
    }
    
    public function test_sort_both_without_comparator(): void
    {
        $data = ['c' => 1, 'a' => 2, 'd' => 1, 'b' => 2];
        $actual = Stream::from($data)->sort(null, Check::BOTH)->toArrayAssoc();
    
        self::assertSame(['c' => 1, 'd' => 1, 'a' => 2, 'b' => 2], $actual);
    }
    
    public function test_sort_both_with_comparator(): void
    {
        $data = ['c' => 1, 'a' => 2, 'd' => 1, 'b' => 2];
        $actual = Stream::from($data)->sort(Comparators::default(), Check::BOTH)->toArrayAssoc();
    
        self::assertSame(['c' => 1, 'd' => 1, 'a' => 2, 'b' => 2], $actual);
    }
    
    public function test_filterBy(): void
    {
        $stream = Stream::from([
            ['id' => 4, 'name' => 'Joe'],
            ['id' => 5, 'name' => 'Cristine'],
        ]);
    
        $actual = $stream->filterBy('name', Filters::length()->ge(5))->toArray();
        self::assertSame([['id' => 5, 'name' => 'Cristine']], $actual);
    }
    
    public function test_sortBy(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
    
        $actual = Stream::from($rowset)->sortBy('age asc', 'name desc', 'id')->toArray();
        
        $expected = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
        ];
        
        self::assertSame($expected, $actual);
    }
    
    public function test_remove_with_single_field(): void
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe', 'age' => 25],
        ]);
        
        self::assertSame([['name' => 'Joe', 'age' => 25]], $stream->remove('id')->toArray());
    }
    
    public function test_remove_with_single_field_as_array(): void
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe', 'age' => 25],
        ]);
        
        self::assertSame([['name' => 'Joe', 'age' => 25]], $stream->remove(['id'])->toArray());
    }
    
    public function test_remove_with_two_fields(): void
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe', 'age' => 25],
        ]);
        
        self::assertSame([['name' => 'Joe']], $stream->remove('id', 'age')->toArray());
    }
    
    public function test_remove_with_two_fields_as_array(): void
    {
        $stream = StreamMaker::from([
            ['id' => 4, 'name' => 'Joe', 'age' => 25],
        ]);
        
        self::assertSame([['name' => 'Joe']], $stream->remove(['id', 'age'])->toArray());
    }
    
    public function test_append(): void
    {
        $stream = Stream::from([4, 3])
            ->mapKey('value')
            ->append('double', static fn(int $v) => 2 * $v);
    
        self::assertSame([
            ['value' => 4, 'double' => 8],
            ['value' => 3, 'double' => 6],
        ], $stream->toArray());
    }
    
    public function test_first(): void
    {
        $item = Stream::from([5, 2, 8])->first();
        
        self::assertTrue($item->found());
        self::assertFalse($item->notFound());
        
        self::assertSame(5, $item->get());
        self::assertSame(0, $item->key());
        
        self::assertSame('5', $item->toString());
        self::assertSame('5', $item->toJson());
        self::assertSame('[5]', $item->toJsonAssoc());
        self::assertSame([5], $item->toArray());
        self::assertSame([5], $item->toArrayAssoc());
        self::assertSame([0, 5], $item->tuple());
    }
    
    public function test_last(): void
    {
        $item = Stream::from([5, 2, 8])->last();
        
        self::assertTrue($item->found());
        self::assertFalse($item->notFound());
        
        self::assertSame(8, $item->get());
        self::assertSame(2, $item->key());
        
        self::assertSame('8', $item->toString());
        self::assertSame('8', $item->toJson());
        self::assertSame('{"2":8}', $item->toJsonAssoc());
        self::assertSame([8], $item->toArray());
        self::assertSame([2 => 8], $item->toArrayAssoc());
        self::assertSame([2, 8], $item->tuple());
    }
    
    public function test_last_default(): void
    {
        $item = Stream::empty()->last('d');
        
        self::assertFalse($item->found());
        self::assertTrue($item->notFound());
        
        self::assertSame('d', $item->get());
        self::assertSame(0, $item->key());
        
        self::assertSame('d', $item->toString());
        self::assertSame('"d"', $item->toJson());
        self::assertSame('["d"]', $item->toJsonAssoc());
        self::assertSame(['d'], $item->toArray());
        self::assertSame(['d'], $item->toArrayAssoc());
        self::assertSame([0, 'd'], $item->tuple());
    }
    
    public function test_unable_to_chain_operation_after_terminating_one(): void
    {
        $this->expectException(\LogicException::class);
        
        $stream = Stream::empty();
        $stream->fold(1, Reducers::average());
        $stream->limit(4);
    }
    
    public function test_find_lazy(): void
    {
        $stream = Stream::from([5, 'a', 2]);
        $item = $stream->find('is_string');
    
        self::assertTrue($item->found());
        self::assertFalse($item->notFound());
    
        self::assertSame('a', $item->get());
        self::assertSame(1, $item->key());
    
        self::assertSame('a', $item->toString());
        self::assertSame('"a"', $item->toJson());
        self::assertSame('{"1":"a"}', $item->toJsonAssoc());
        self::assertSame(['a'], $item->toArray());
        self::assertSame([1 => 'a'], $item->toArrayAssoc());
        self::assertSame([1, 'a'], $item->tuple());
    }
    
    public function test_count_lazy(): void
    {
        $stream = Stream::from([5, 'a', 2]);
        $count = $stream->filter('is_string')->count();
    
        self::assertTrue($count->found());
        self::assertFalse($count->notFound());
    
        self::assertSame(1, $count->get());
        self::assertSame(0, $count->key());
    
        self::assertSame('1', $count->toString());
        self::assertSame('1', $count->toJson());
        self::assertSame('[1]', $count->toJsonAssoc());
        self::assertSame([1], $count->toArray());
        self::assertSame([1], $count->toArrayAssoc());
        self::assertSame([0, 1], $count->tuple());
    }
}