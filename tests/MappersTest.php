<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Mapper\Cast\ToBool;
use FiiSoft\Jackdaw\Mapper\Cast\ToFloat;
use FiiSoft\Jackdaw\Mapper\Cast\ToInt;
use FiiSoft\Jackdaw\Mapper\Cast\ToString;
use FiiSoft\Jackdaw\Mapper\Concat;
use FiiSoft\Jackdaw\Mapper\Exception\MapperExceptionFactory;
use FiiSoft\Jackdaw\Mapper\JsonDecode;
use FiiSoft\Jackdaw\Mapper\JsonEncode;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Reverse;
use FiiSoft\Jackdaw\Mapper\Split;
use FiiSoft\Jackdaw\Mapper\Trim;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\TestCase;

final class MappersTest extends TestCase
{
    /**
     * @dataProvider getDataForTestExtractThrowsExceptionOnInvalidParam
     */
    public function test_Extract_throws_exception_on_invalid_param($fields): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('field'));
        
        Mappers::extract($fields);
    }
    
    public function test_Split_throws_exception_when_separator_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('separator'));
        
        Mappers::split('');
    }
    
    public static function getDataForTestExtractThrowsExceptionOnInvalidParam(): \Generator
    {
        $fields = [
            [],
            '',
            true,
            [''],
            [[]],
            [false],
        ];
    
        foreach ($fields as $field) {
            yield [$field];
        }
    }
    
    public function test_GenericMapper_throws_exception_when_callable_does_not_accept_required_num_of_arguments(): void
    {
        $this->expectExceptionObject(MapperExceptionFactory::invalidParamMapper(3));
        
        Mappers::getAdapter(static fn($a, $b, $c): bool => true)->map('a');
    }
    
    public function test_GenericMapper_can_call_callable_without_arguments(): void
    {
        self::assertSame('foo', Mappers::getAdapter(static fn(): string => 'foo')->map('bar'));
    }
    
    public function test_GenericMapper_can_call_callable_without_arguments_when_iterate_stream(): void
    {
        $mapper = Mappers::getAdapter(static fn(): string => 'foo');
        
        $result = [];
        foreach ($mapper->buildStream([1, 2, 3]) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame(['foo', 'foo', 'foo'], $result);
    }
    
    public function test_JsonDecode(): void
    {
        self::assertSame([['a' => 1, 'b' => 2]], Mappers::jsonDecode()->map('[{"a":1,"b":2}]'));
    }
    
    public function test_JsonEncode(): void
    {
        self::assertSame('[{"a":1,"b":2}]', Mappers::jsonEncode()->map([['a' => 1, 'b' => 2]]));
    }
    
    public function test_JsonEncode_can_iterate_stream(): void
    {
        $data = [
            ['a' => 1, 'b' => 2],
            ['c' => 3, 'd' => 4],
        ];
        
        $result = [];
        foreach (Mappers::jsonEncode()->buildStream($data) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame(['{"a":1,"b":2}', '{"c":3,"d":4}'], $result);
    }
    
    public function test_JsonDecode_can_iterate_stream(): void
    {
        $data = ['{"a":1,"b":2}', '{"c":3,"d":4}'];
        
        $result = [];
        foreach (Mappers::jsonDecode()->buildStream($data) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame([
            ['a' => 1, 'b' => 2],
            ['c' => 3, 'd' => 4],
        ], $result);
    }
    
    public function test_toString_can_map_simple_value_and_also_fields_in_arrays(): void
    {
        self::assertSame('15', Mappers::toString()->map(15));
        self::assertSame(['field' => '5'], Mappers::toString(['field'])->map(['field' => 5]));
    }
    
    public function test_toBool_can_map_simple_value_and_also_fields_in_arrays(): void
    {
        self::assertTrue(Mappers::toBool()->map(15));
        self::assertSame(['field' => true], Mappers::toBool(['field'])->map(['field' => 5]));
    }
    
    public function test_toFloat(): void
    {
        self::assertSame(15.45, Mappers::toFloat()->map('15.45'));
    }
    
    public function test_Remove_throws_exception_on_invalid_param(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('fields'));
        
        Mappers::remove([]);
    }
    
    public function test_Remove_from_array(): void
    {
        $mapper = Mappers::remove('id');
        
        self::assertSame(['name' => 'Joe'], $mapper->map(['id' => 1, 'name' => 'Joe']));
    }
    
    public function test_Remove_from_Traversable(): void
    {
        $mapper = Mappers::remove('id');
        
        $value = new \ArrayObject(['id' => 1, 'name' => 'Joe']);
        
        self::assertEquals(new \ArrayObject(['name' => 'Joe']), $mapper->map($value));
    }
    
    public function test_Remove_from_Traversable_in_iterable_stream(): void
    {
        $item = new \ArrayObject(['id' => 1, 'name' => 'Joe']);
        
        $result = [];
        foreach (Mappers::remove('id')->buildStream([$item]) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertEquals([new \ArrayObject(['name' => 'Joe'])], $result);
    }
    
    public function test_Remove_throws_exception_on_invalid_argument(): void
    {
        $this->expectExceptionObject(MapperExceptionFactory::unsupportedValue(new \stdClass()));
        
        Mappers::remove('id')->map(new \stdClass());
    }
    
    public function test_Remove_throws_exception_on_invalid_argument_when_iterate_over_stream(): void
    {
        $this->expectExceptionObject(MapperExceptionFactory::unsupportedValue(new \stdClass()));
        
        foreach (Mappers::remove('id')->buildStream([new \stdClass()]) as $_) {
            //noop
        }
    }
    
    public function test_Reverse_can_handle_string(): void
    {
        self::assertSame('dcba', Mappers::reverse()->map('abcd'));
    }
    
    public function test_Reverse_throws_exception_on_invalid_argument(): void
    {
        $this->expectExceptionObject(MapperExceptionFactory::unableToReverse(15));
        
        Mappers::reverse()->map(15);
    }
    
    public function test_ToInt(): void
    {
        self::assertSame(16, Mappers::toInt()->map('16'));
    }
    
    public function test_ToInt_throws_exception_on_invalid_param(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('fields'));
        
        Mappers::toInt([]);
    }
    
    public function test_ToInt_with_many_fields(): void
    {
        $actual = Mappers::toInt(['id', 'pid'])->map(['id' => '1', 'pid' => '3', 'other' => 'str']);
        
        self::assertSame(['id' => 1, 'pid' => 3, 'other' => 'str'], $actual);
    }
    
    public function test_ToFloat_throws_exception_on_invalid_param(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('fields'));
        
        Mappers::toFloat([]);
    }
    
    public function test_ToFloat_with_many_fields(): void
    {
        $actual = Mappers::toFloat(['val1', 'val2'])->map(['val1' => '10', 'val2' => '3.5', 'other' => 'str']);
        
        self::assertSame(['val1' => 10.0, 'val2' => 3.5, 'other' => 'str'], $actual);
    }
    
    public function test_ToFloat_with_many_fields_iterate_stream(): void
    {
        $mapper = Mappers::toFloat(['val1', 'val2'])->buildStream([
            ['val1' => '10', 'val2' => '3.5', 'other' => 'foo'],
            ['val1' => '3', 'val2' => '8.9', 'other' => 'bar'],
        ]);
        
        $result = [];
        foreach ($mapper as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame([
            ['val1' => 10.0, 'val2' => 3.5, 'other' => 'foo'],
            ['val1' => 3.0, 'val2' => 8.9, 'other' => 'bar'],
        ], $result);
    }
    
    public function test_Append(): void
    {
        self::assertSame(['z' => 2, 'a' => 15], Mappers::append('a', 15)->map(2, 'z'));
    }
    
    public function test_Append_replaces_existing_key(): void
    {
        self::assertSame(['a' => 15], Mappers::append('a', 15)->map(2, 'a'));
    }
    
    public function test_Append_can_reduce_array_to_single_field(): void
    {
        self::assertSame([1, 2, 3, 'sum' => 6], Mappers::append('sum', Reducers::sum())->map([1, 2, 3]));
    }
    
    public function test_Simple(): void
    {
        self::assertSame('g', Mappers::simple('g')->map(5, 'a'));
    }
    
    public function test_Simple_iterable_stream(): void
    {
        $result = [];
        foreach (Mappers::simple('foo')->buildStream([6, 'a', true]) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame(['foo', 'foo', 'foo'], $result);
    }
    
    public function test_MoveTo_creates_array_with_key_from_value(): void
    {
        $mapper = Mappers::moveTo('key');
        
        self::assertSame(['key' => 15], $mapper->map(15));
        self::assertSame(['key' => [2, 3]], $mapper->map([2, 3]));
    }
    
    public function test_MoveTo_creates_array_with_value_and_key_moved_to_fields(): void
    {
        $mapper = Mappers::moveTo('value', 'key');
        
        self::assertSame(['key' => 4, 'value' => 'foo'], $mapper->map('foo', 4));
    }
    
    public function test_MoveTo_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('field'));
        
        Mappers::moveTo('');
    }
    
    public function test_MoveTo_throws_exception_when_param_key_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('key'));
    
        Mappers::moveTo('field', '');
    }
    
    public function test_Round(): void
    {
        self::assertSame(5.47, Mappers::round()->map(5.4689));
        self::assertSame(5.47, Mappers::round()->map('5.4689'));
        
        self::assertSame(5.0, Mappers::round()->map(5.0));
        self::assertSame(5.0, Mappers::round()->map('5.0'));
        
        self::assertSame(5, Mappers::round()->map(5));
    }
    
    public function test_Round_can_iterate_stream(): void
    {
        $result = [];
        foreach (Mappers::round()->buildStream([5.4689, 5.0, 5, '5.27']) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame([5.47, 5.0, 5, 5.27], $result);
    }
    
    public function test_Round_throws_exception_when_argument_is_invalid(): void
    {
        $value = 'this is not a number';
        $this->expectExceptionObject(MapperExceptionFactory::unableToRoundValue($value));
        
        Mappers::round()->map($value);
    }
    
    public function test_Round_throws_exception_when_argument_is_invalid_and_stream_is_iterated(): void
    {
        $value = 'this is not a number';
        $this->expectExceptionObject(MapperExceptionFactory::unableToRoundValue($value));
        
        foreach (Mappers::round()->buildStream([5, '8', $value, 3.245]) as $_) {
            //noop
        }
    }
    
    public function test_getAdapter_can_create_Mapper_from_various_arguments(): void
    {
        self::assertInstanceOf(ToInt::class, Mappers::getAdapter('intval'));
        self::assertInstanceOf(ToInt::class, Mappers::getAdapter('\intval'));
        
        self::assertInstanceOf(ToString::class, Mappers::getAdapter('strval'));
        self::assertInstanceOf(ToString::class, Mappers::getAdapter('\strval'));
        
        self::assertInstanceOf(ToFloat::class, Mappers::getAdapter('floatval'));
        self::assertInstanceOf(ToFloat::class, Mappers::getAdapter('\floatval'));
        
        self::assertInstanceOf(ToBool::class, Mappers::getAdapter('boolval'));
        self::assertInstanceOf(ToBool::class, Mappers::getAdapter('\boolval'));
        
        self::assertInstanceOf(Concat::class, Mappers::getAdapter('implode'));
        self::assertInstanceOf(Concat::class, Mappers::getAdapter('\implode'));
        
        self::assertInstanceOf(Split::class, Mappers::getAdapter('explode'));
        self::assertInstanceOf(Split::class, Mappers::getAdapter('\explode'));
        
        self::assertInstanceOf(Reverse::class, Mappers::getAdapter('array_reverse'));
        self::assertInstanceOf(Reverse::class, Mappers::getAdapter('\array_reverse'));
        
        self::assertInstanceOf(JsonEncode::class, Mappers::getAdapter('json_encode'));
        self::assertInstanceOf(JsonEncode::class, Mappers::getAdapter('\json_encode'));
        
        self::assertInstanceOf(JsonDecode::class, Mappers::getAdapter('json_decode'));
        self::assertInstanceOf(JsonDecode::class, Mappers::getAdapter('\json_decode'));
        
        self::assertInstanceOf(Trim::class, Mappers::getAdapter('trim'));
        self::assertInstanceOf(Trim::class, Mappers::getAdapter('\trim'));
        
        self::assertInstanceOf(ReducerAdapter::class, Mappers::getAdapter(Reducers::max()));
    }
    
    public function test_MapField_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('field'));
    
        Mappers::mapField('', Mappers::reverse());
    }
    
    public function test_Complete_replaces_only_null_or_missing_keys(): void
    {
        $mapper = Mappers::complete('name', 'anonymous');
        
        self::assertSame(['id' => 3, 'name' => 'Ole'], $mapper->map(['id' => 3, 'name' => 'Ole']));
        self::assertSame(['id' => 3, 'name' => 'anonymous'], $mapper->map(['id' => 3, 'name' => null]));
        self::assertSame(['id' => 3, 'name' => 'anonymous'], $mapper->map(['id' => 3]));
    }
    
    public function test_Complete_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('field'));
        
        Mappers::complete('', Mappers::reverse());
    }
    
    public function test_Complete_with_scalar_value(): void
    {
        $expected = [1 => 'string', 'reversed' => 'gnirts'];
        
        self::assertSame($expected, Mappers::complete('reversed', 'strrev')->map('string', 1));
    }
    
    public function test_Complete_can_reduce_array_value_to_single_field(): void
    {
        self::assertSame([1, 2, 3, 'sum' => 6], Mappers::complete('sum', Reducers::sum())->map([1, 2, 3]));
    }
    
    public function test_Append_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('field'));
        
        Mappers::append('', Mappers::reverse());
    }
    
    public function test_Tokenize_changes_string_to_array(): void
    {
        $result = Mappers::tokenize(' ')->map(' this is   string to  tokenize  by space ');
        
        self::assertSame(['this', 'is', 'string', 'to', 'tokenize', 'by', 'space'], $result);
    }
    
    public function test_Remap_throws_exception_when_param_keys_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('keys'));
        
        Mappers::remap([]);
    }
    
    public function test_Remap_throws_exception_when_param_keys_contains_invalid_key(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('keys'));
        
        Mappers::remap(['' => 'foo']);
    }
    
    public function test_Remap_throws_exception_when_param_keys_contains_invalid_value(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('keys'));
        
        Mappers::remap(['foo' => '']);
    }
    
    public function test_Remap_can_merge_with_other_Remap_mapper(): void
    {
        $first = Mappers::remap([2 => 'foo']);
        $second = Mappers::remap(['foo' => 'bar', 1 => 'moo']);
        
        self::assertTrue($first->mergeWith($second));
        
        self::assertSame(
            ['bar' => 'hello', 'moo' => 'world'],
            $first->map([1 => 'world', 2 => 'hello'])
        );
    }
    
    public function test_mappers_cannot_merge_with_incompatible_mappers(): void
    {
        self::assertFalse(Mappers::remap(['foo' => 'bar'])->mergeWith(Mappers::concat()));
        self::assertFalse(Mappers::remove('foo')->mergeWith(Mappers::concat()));
        self::assertFalse(Mappers::round()->mergeWith(Mappers::concat()));
        self::assertFalse(Mappers::trim()->mergeWith(Mappers::concat()));
        self::assertFalse(Mappers::simple('foo')->mergeWith(Mappers::concat()));
        self::assertFalse(Mappers::toBool()->mergeWith(Mappers::concat()));
    }
    
    public function test_Remove_can_merge_with_other_Remove_mapper(): void
    {
        $first = Mappers::remove('foo');
        $second = Mappers::remove('bar');
        
        self::assertTrue($first->mergeWith($second));
        
        self::assertSame(['zoe' => 3], $first->map(['foo' => 1, 'bar' => 2, 'zoe' => 3]));
    }
    
    public function test_Round_throws_exception_when_param_precision_is_too_low(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('precision'));
        
        Mappers::round(-1);
    }
    
    public function test_Round_throws_exception_when_param_precision_is_too_high(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('precision'));
        
        Mappers::round(17);
    }
    
    public function test_Round_can_merge_with_other_Round_mapper(): void
    {
        $first = Mappers::round(5);
        $second = Mappers::round(3);
        
        self::assertTrue($first->mergeWith($second));
        
        self::assertSame(4.286, $first->map(4.286356));
    }
    
    public function test_Simple_can_merge_with_other_Simple_mapper(): void
    {
        $first = Mappers::simple('foo');
        $second = Mappers::simple('bar');
        
        self::assertTrue($first->mergeWith($second));
        
        self::assertSame('bar', $first->map('zoe'));
    }
    
    public function test_Trim_can_merge_with_other_Trim_mapper(): void
    {
        $first = Mappers::trim('.');
        $second = Mappers::trim(',');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame('foo', $first->map(',.foo,.'));
    }
    
    public function test_ToBool_can_merge_with_other_ToBool_mapper(): void
    {
        $first = Mappers::toBool('foo');
        $second = Mappers::toBool('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => true, 'bar' => false], $first->map(['foo' => 1, 'bar' => 0]));
    }
    
    public function test_ToInt_can_merge_with_other_ToInt_mapper(): void
    {
        $first = Mappers::toInt('foo');
        $second = Mappers::toInt('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => 1, 'bar' => 2], $first->map(['foo' => 1.0, 'bar' => 2.0]));
    }
    
    public function test_ToFloat_can_merge_with_other_ToFloat_mapper(): void
    {
        $first = Mappers::toFloat('foo');
        $second = Mappers::toFloat('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => 1.0, 'bar' => 0.0], $first->map(['foo' => 1, 'bar' => 0]));
    }
    
    public function test_ToString_can_merge_with_other_ToString_mapper(): void
    {
        $first = Mappers::toString('foo');
        $second = Mappers::toString('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => '1', 'bar' => '0'], $first->map(['foo' => 1, 'bar' => 0]));
    }
    
    public function test_two_different_simple_cast_mappers_dont_merge(): void
    {
        $toBool = Mappers::toBool();
        $toFloat = Mappers::toFloat();
        $toInt = Mappers::toInt();
        $toString = Mappers::toString();
        
        self::assertFalse($toBool->mergeWith($toFloat));
        self::assertFalse($toFloat->mergeWith($toInt));
        self::assertFalse($toInt->mergeWith($toString));
        self::assertFalse($toString->mergeWith($toBool));
    }
    
    public function test_two_different_field_cast_mappers_dont_merge(): void
    {
        $toBool = Mappers::toBool('foo');
        $toFloat = Mappers::toFloat('bar');
        $toInt = Mappers::toInt('zoo');
        $toString = Mappers::toString('joe');
        
        self::assertFalse($toBool->mergeWith($toFloat));
        self::assertFalse($toFloat->mergeWith($toInt));
        self::assertFalse($toInt->mergeWith($toString));
        self::assertFalse($toString->mergeWith($toBool));
    }
    
    public function test_ToArray_changes_every_argument_into_array(): void
    {
        $withoutKey = Mappers::toArray();
        
        self::assertSame(['a'], $withoutKey->map('a'));
        self::assertSame([4], $withoutKey->map(4));
        self::assertSame(['foo' => 'bar'], $withoutKey->map(['foo' => 'bar']));
        self::assertSame(['foo' => 'bar'], $withoutKey->map(new \ArrayObject(['foo' => 'bar'])));
        
        $withKey = Mappers::toArray(true);
        
        self::assertSame([1 => 'a'], $withKey->map('a', 1));
        self::assertSame([1 => 4], $withKey->map(4, 1));
        self::assertSame(['foo' => 'bar'], $withKey->map(['foo' => 'bar'], 1));
        self::assertSame(['foo' => 'bar'], $withKey->map(new \ArrayObject(['foo' => 'bar']), 1));
    }
    
    public function test_ToArray_build_iterable_stream_append_keys(): void
    {
        $data = [1 => 'a', 3 => 4, 5 => ['foo' => 'bar'], 7 => new \ArrayObject(['joe' => 'doe'])];
        
        $result = [];
        foreach (Mappers::toArray(true)->buildStream($data) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame([1 => [1 => 'a'], 3 => [3 => 4], 5 => ['foo' => 'bar'], 7 => ['joe' => 'doe']], $result);
    }
    
    public function test_ToArray_build_iterable_stream_dont_append_keys(): void
    {
        $data = [1 => 'a', 3 => 4, 5 => ['foo' => 'bar'], 7 => new \ArrayObject(['joe' => 'doe'])];
        
        $result = [];
        foreach (Mappers::toArray()->buildStream($data) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame([1 => ['a'], 3 => [4], 5 => ['foo' => 'bar'], 7 => ['joe' => 'doe']], $result);
    }
    
    public function test_Shuffle_can_mix_elements_in_arrays(): void
    {
        $array = \range(1, 1000);
        
        self::assertNotSame($array, Mappers::shuffle()->map($array));
    }
    
    public function test_Shuffle_can_mix_chars_in_strings(): void
    {
        $string = \str_repeat(\implode('', \range('a', 'z')), 10);
        
        self::assertNotSame($string, Mappers::shuffle()->map($string));
    }
    
    public function test_Shuffle_can_transform_iterator_into_array_with_mixed_elements(): void
    {
        $array = \range(1, 1000);
        $iterator = new \ArrayIterator($array);
        
        self::assertNotSame($array, Mappers::getAdapter('\shuffle')->map($iterator));
    }
    
    public function test_split(): void
    {
        self::assertSame(['a', 'b', 'c'], Mappers::split()->map('a b c', 1));
    }
    
    public function test_Filter_used_as_mapper_allows_to_remove_elements_in_arrays_1(): void
    {
        $data = ['a', 1, 'b', 2, 'c', 3];
        
        self::assertSame([1 => 1, 3 => 2, 5 => 3], Mappers::getAdapter(Filters::isInt())->map($data));
    }
    
    public function test_Filter_used_as_mapper_allows_to_remove_elements_in_arrays_2(): void
    {
        $data = ['a', 1, 'b', 2, 'c', 3];
        
        self::assertSame([1 => 1, 5 => 3], Mappers::getAdapter(Filters::onlyIn([1, 3]))->map($data));
    }
    
    public function test_FieldValue_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('field'));
        
        Mappers::fieldValue(false);
    }
    
    public function test_FieldValue_allows_to_merge_only_with_other_FieldValue_mapper(): void
    {
        $first = Mappers::fieldValue('foo');
        $second = Mappers::fieldValue('bar');
        
        self::assertTrue($first->mergeWith($second));
        self::assertFalse($first->mergeWith(Mappers::simple('zoo')));
        
        self::assertSame(2, $first->map(['foo' => 1, 'bar' => 2]));
    }
    
    public function test_Value_simply_returns_value(): void
    {
        self::assertSame('foo', Mappers::value()->map('foo'));
    }
    
    public function test_Key_simply_returns_key(): void
    {
        self::assertSame(1, Mappers::key()->map('foo', 1));
    }
    
    public function test_Value_can_merge_only_with_other_Value(): void
    {
        self::assertTrue(Mappers::value()->mergeWith(Mappers::value()));
        self::assertFalse(Mappers::value()->mergeWith(Mappers::key()));
    }
    
    public function test_Key_can_merge_only_with_other_Key(): void
    {
        self::assertTrue(Mappers::key()->mergeWith(Mappers::key()));
        self::assertFalse(Mappers::key()->mergeWith(Mappers::value()));
    }
    
    public function test_MultiMapper_throws_exception_when_initial_array_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('pattern'));
        
        Mappers::getAdapter([]);
    }
    
    public function test_Replace(): void
    {
        self::assertSame('abc', Mappers::replace(' ', '')->map('a b c'));
    }
    
    public function test_Replace_can_map_stream(): void
    {
        $result = [];
        foreach (Mappers::replace('a', '*')->buildStream(['ooao', 'eeea', 'aiiia']) as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame(['oo*o', 'eee*', '*iii*'], $result);
    }
    
    public function test_Replace_throws_exception_on_wrong_type_of_param_search(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('search'));
        
        Mappers::replace(5, '');
    }
    
    public function test_Replace_throws_exception_on_wrong_type_of_param_replace(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('replace'));
        
        Mappers::replace(' ', 5);
    }
    
    public function test_Replace_throws_exception_when_param_search_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('search'));
        
        Mappers::replace('', '');
    }
    
    public function test_FieldValue_can_handle_ArrayAccess_implementations(): void
    {
        self::assertSame('a', Mappers::fieldValue('foo')->map(new \ArrayObject(['foo' => 'a'])));
    }
    
    public function test_MapField_can_handle_ArrayAccess_implementations(): void
    {
        self::assertEquals(
            new \ArrayObject(['foo' => 6]),
            Mappers::mapField('foo', static fn(int $v): int => $v * 2)->map(new \ArrayObject(['foo' => 3]))
        );
    }
    
    public function test_Increment_can_increment_integer_values(): void
    {
        self::assertSame(7, Mappers::increment(2)->map(5));
    }
    
    public function test_Increment_can_decrement_integer_values(): void
    {
        self::assertSame(3, Mappers::decrement(2)->map(5));
    }
    
    public function test_Increment_throws_exception_when_param_step_is_zero(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('step'));
        
        Mappers::increment(0);
    }
}