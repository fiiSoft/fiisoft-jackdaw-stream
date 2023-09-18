<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Mapper\Concat;
use FiiSoft\Jackdaw\Mapper\JsonDecode;
use FiiSoft\Jackdaw\Mapper\JsonEncode;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Reverse;
use FiiSoft\Jackdaw\Mapper\Split;
use FiiSoft\Jackdaw\Mapper\ToBool;
use FiiSoft\Jackdaw\Mapper\ToFloat;
use FiiSoft\Jackdaw\Mapper\ToInt;
use FiiSoft\Jackdaw\Mapper\ToString;
use FiiSoft\Jackdaw\Mapper\Trim;
use FiiSoft\Jackdaw\Predicate\Predicates;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

final class MappersTest extends TestCase
{
    public function test_Concat_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::concat()->map('string', 1);
    }
    
    /**
     * @dataProvider getDataForTestExtractThrowsExceptionOnInvalidParam
     */
    public function test_Extract_throws_exception_on_invalid_param($fields): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param fields');
        
        Mappers::extract($fields);
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
    
    public function test_Extract_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::extract(['field'])->map('not_an_array', 1);
    }
    
    public function test_GenericMapper_throws_exception_when_callable_does_not_accept_required_num_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::generic(static fn($a, $b, $c): bool => true)->map('a', 1);
    }
    
    public function test_GenericMapper_can_call_callable_without_arguments(): void
    {
        self::assertSame('foo', Mappers::generic(static fn(): string => 'foo')->map('bar', 1));
    }
    
    public function test_JsonDecode(): void
    {
        self::assertSame([['a' => 1, 'b' => 2]], Mappers::jsonDecode()->map('[{"a":1,"b":2}]', 1));
    }
    
    public function test_JsonDecode_throws_exception_on_invalid_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::jsonDecode()->map(15, 1);
    }
    
    public function test_JsonEncode(): void
    {
        self::assertSame('[{"a":1,"b":2}]', Mappers::jsonEncode()->map([['a' => 1, 'b' => 2]], 1));
    }
    
    public function test_toString_can_map_simple_value_and_also_fields_in_arrays(): void
    {
        self::assertSame('15', Mappers::toString()->map(15, 1));
        self::assertSame(['field' => '5'], Mappers::toString(['field'])->map(['field' => 5], 1));
    }
    
    public function test_toString_throws_exception_when_scalar_value_is_required(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to cast to string param array');
        
        Mappers::toString()->map(['field' => 5], 1);
    }
    
    public function test_toString_throws_exception_when_array_value_is_required(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to cast to string param integer when array is required');
        
        Mappers::toString(['field'])->map(5, 1);
    }
    
    public function test_toBool_can_map_simple_value_and_also_fields_in_arrays(): void
    {
        self::assertTrue(Mappers::toBool()->map(15, 1));
        self::assertSame(['field' => true], Mappers::toBool(['field'])->map(['field' => 5], 1));
    }
    
    public function test_toBool_throws_exception_when_scalar_value_is_required(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to cast to bool param array');
        
        Mappers::toBool()->map(['field' => 5], 1);
    }
    
    public function test_toBool_throws_exception_when_array_value_is_required(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to cast to bool param integer when array is required');
        
        Mappers::toBool(['field'])->map(5, 1);
    }
    
    public function test_toFloat(): void
    {
        self::assertSame(15.45, Mappers::toFloat()->map('15.45', 1));
    }
    
    public function test_Remove_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Mappers::remove([]);
    }
    
    public function test_Remove_from_array(): void
    {
        $mapper = Mappers::remove('id');
        
        self::assertSame(['name' => 'Joe'], $mapper->map(['id' => 1, 'name' => 'Joe'], 1));
    }
    
    public function test_Remove_from_Traversable(): void
    {
        $mapper = Mappers::remove('id');
        
        $value = new \ArrayObject(['id' => 1, 'name' => 'Joe']);
        
        self::assertEquals(new \ArrayObject(['name' => 'Joe']), $mapper->map($value, 1));
    }
    
    public function test_Remove_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::remove('id')->map(new \stdClass(), 1);
    }
    
    public function test_Reverse_can_handle_string(): void
    {
        self::assertSame('dcba', Mappers::reverse()->map('abcd', 1));
    }
    
    public function test_Reverse_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::reverse()->map(15, 1);
    }
    
    public function test_Split_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::split()->map(15, 1);
    }
    
    public function test_ToInt_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Mappers::toInt([]);
    }
    
    public function test_ToInt_with_many_fields(): void
    {
        $mapper = Mappers::toInt(['id', 'pid']);
        $actual = $mapper->map(['id' => '1', 'pid' => '3', 'other' => 'str'], 1);
        self::assertSame(['id' => 1, 'pid' => 3, 'other' => 'str'], $actual);
    }
    
    public function test_ToInt_throws_exception_on_invalid_argument(): void
    {
        try {
            Mappers::toInt()->map(['id' => 1], 1);
            self::fail('Exception expected');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::assertInstanceOf(\LogicException::class, $e);
            self::assertSame('Unable to cast to int param array', $e->getMessage());
        }
        
        try {
            Mappers::toInt('id')->map(15, 1);
            self::fail('Exception expected');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::assertInstanceOf(\LogicException::class, $e);
            self::assertSame('Unable to cast to int param integer', $e->getMessage());
        }
    }
    
    public function test_ToFloat_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Mappers::toFloat([]);
    }
    
    public function test_ToFloat_with_many_fields(): void
    {
        $mapper = Mappers::toFloat(['val1', 'val2']);
        $actual = $mapper->map(['val1' => '10', 'val2' => '3.5', 'other' => 'str'], 1);
        self::assertSame(['val1' => 10.0, 'val2' => 3.5, 'other' => 'str'], $actual);
    }
    
    public function test_ToFloat_throws_exception_on_invalid_argument(): void
    {
        try {
            Mappers::toFloat()->map(['id' => 1], 1);
            self::fail('Exception expected');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::assertInstanceOf(\LogicException::class, $e);
            self::assertSame('Unable to cast to float param array', $e->getMessage());
        }
        
        try {
            Mappers::toFloat('id')->map(15, 1);
            self::fail('Exception expected');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::assertInstanceOf(\LogicException::class, $e);
            self::assertSame('Unable to cast to float param integer', $e->getMessage());
        }
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
        self::assertSame([1, 2, 3, 'sum' => 6], Mappers::append('sum', Reducers::sum())->map([1, 2, 3], 1));
    }
    
    public function test_Simple(): void
    {
        self::assertSame('g', Mappers::simple('g')->map(5, 'a'));
    }
    
    public function test_MoveTo_creates_array_with_key_from_value(): void
    {
        $mapper = Mappers::moveTo('key');
        
        self::assertSame(['key' => 15], $mapper->map(15, 3));
        self::assertSame(['key' => [2, 3]], $mapper->map([2, 3], 'a'));
    }
    
    public function test_MoveTo_creates_array_with_value_and_key_moved_to_fields(): void
    {
        $mapper = Mappers::moveTo('value', 'key');
        
        self::assertSame(['key' => 4, 'value' => 'foo'], $mapper->map('foo', 4));
    }
    
    public function test_MoveTo_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
        
        Mappers::moveTo('');
    }
    
    public function test_MoveTo_throws_exception_when_param_key_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param key');
    
        Mappers::moveTo('field', '');
    }
    
    public function test_Round(): void
    {
        self::assertSame(5.47, Mappers::round()->map(5.4689, 1));
        self::assertSame(5.47, Mappers::round()->map('5.4689', 1));
        
        self::assertSame(5.0, Mappers::round()->map(5.0, 1));
        self::assertSame(5.0, Mappers::round()->map('5.0', 1));
        
        self::assertSame(5, Mappers::round()->map(5, 1));
    }
    
    public function test_Round_throws_exception_when_argument_is_invalid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to round non-number value string');
        
        Mappers::round()->map('this is not a number', 1);
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
    
        Mappers::mapField('', Mappers::reverse());
    }
    
    public function test_MapField_throws_exception_when_value_has_not_required_field(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Field key does not exist in value');
    
        Mappers::mapField('key', Mappers::reverse())->map(['no_key' => 'abc'], 1);
    }
    
    public function test_MapField_throws_exception_when_value_is_not_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to map field key because value is string');
    
        Mappers::mapField('key', Mappers::reverse())->map('string value', 1);
    }
    
    public function test_Complete_replaces_only_null_or_missing_keys(): void
    {
        $mapper = Mappers::complete('name', 'anonymous');
        
        self::assertSame(['id' => 3, 'name' => 'Ole'], $mapper->map(['id' => 3, 'name' => 'Ole'], 0));
        self::assertSame(['id' => 3, 'name' => 'anonymous'], $mapper->map(['id' => 3, 'name' => null], 0));
        self::assertSame(['id' => 3, 'name' => 'anonymous'], $mapper->map(['id' => 3], 0));
    }
    
    public function test_Complete_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
        
        Mappers::complete('', Mappers::reverse());
    }
    
    public function test_Complete_with_scalar_value(): void
    {
        $expected = [1 => 'string', 'reversed' => 'gnirts'];
        
        self::assertSame($expected, Mappers::complete('reversed', 'strrev')->map('string', 1));
    }
    
    public function test_Complete_can_reduce_array_value_to_single_field(): void
    {
        self::assertSame([1, 2, 3, 'sum' => 6], Mappers::complete('sum', Reducers::sum())->map([1, 2, 3], 1));
    }
    
    public function test_Append_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
    
        Mappers::append('', Mappers::reverse());
    }
    
    public function test_ReducerAdapter_throws_exception_when_value_is_not_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to reduce string because it is not iterable');
    
        Mappers::getAdapter(Reducers::max())->map('string', 1);
    }
    
    public function test_Tokenize_changes_string_to_array(): void
    {
        $result = Mappers::tokenize(' ')->map(' this is   string to  tokenize  by space ', 1);
        
        self::assertSame(['this', 'is', 'string', 'to', 'tokenize', 'by', 'space'], $result);
    }
    
    public function test_Tokenize_throws_exception_when_value_is_not_string(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Value must be a string to tokenize it');
        
        Mappers::tokenize()->map(5, 2);
    }
    
    public function test_Trim_trims_strings_and_returns_non_strims_unafected(): void
    {
        $mapper = Mappers::trim();
        
        self::assertSame('foo', $mapper->map(' foo ', 1));
        self::assertSame(5, $mapper->map(5, 1));
    }
    
    public function test_Remap_throws_exception_when_param_keys_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param keys');
        
        Mappers::remap([]);
    }
    
    public function test_Remap_throws_exception_when_param_keys_contains_invalid_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid element in param keys');
        
        Mappers::remap(['' => 'foo']);
    }
    
    public function test_Remap_throws_exception_when_param_keys_contains_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid element in param keys');
        
        Mappers::remap(['foo' => '']);
    }
    
    public function test_Remap_throws_exception_when_value_to_map_is_not_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to remap keys in value which is not an array');
        
        Mappers::remap(['foo' => 'bar'])->map(5, 1);
    }
    
    public function test_Remap_can_merge_with_other_Remap_mapper(): void
    {
        $first = Mappers::remap([2 => 'foo']);
        $second = Mappers::remap(['foo' => 'bar', 1 => 'moo']);
        
        self::assertTrue($first->mergeWith($second));
        
        self::assertSame(
            ['bar' => 'hello', 'moo' => 'world'],
            $first->map([1 => 'world', 2 => 'hello'], 1)
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
        
        self::assertSame(['zoe' => 3], $first->map(['foo' => 1, 'bar' => 2, 'zoe' => 3], 1));
    }
    
    public function test_Round_throws_exception_when_param_precision_is_too_low(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param precision');
        
        Mappers::round(-1);
    }
    
    public function test_Round_throws_exception_when_param_precision_is_too_high(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param precision');
        
        Mappers::round(17);
    }
    
    public function test_Round_can_merge_with_other_Round_mapper(): void
    {
        $first = Mappers::round(5);
        $second = Mappers::round(3);
        
        self::assertTrue($first->mergeWith($second));
        
        self::assertSame(4.286, $first->map(4.286356, 1));
    }
    
    public function test_Simple_can_merge_with_other_Simple_mapper(): void
    {
        $first = Mappers::simple('foo');
        $second = Mappers::simple('bar');
        
        self::assertTrue($first->mergeWith($second));
        
        self::assertSame('bar', $first->map('zoe', 1));
    }
    
    public function test_Trim_can_merge_with_other_Trim_mapper(): void
    {
        $first = Mappers::trim('.');
        $second = Mappers::trim(',');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame('foo', $first->map(',.foo,.', 1));
    }
    
    public function test_ToBool_can_merge_with_other_ToBool_mapper(): void
    {
        $first = Mappers::toBool('foo');
        $second = Mappers::toBool('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => true, 'bar' => false], $first->map(['foo' => 1, 'bar' => 0], 1));
    }
    
    public function test_ToInt_can_merge_with_other_ToInt_mapper(): void
    {
        $first = Mappers::toInt('foo');
        $second = Mappers::toInt('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => 1, 'bar' => 2], $first->map(['foo' => 1.0, 'bar' => 2.0], 1));
    }
    
    public function test_ToFloat_can_merge_with_other_ToFloat_mapper(): void
    {
        $first = Mappers::toFloat('foo');
        $second = Mappers::toFloat('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => 1.0, 'bar' => 0.0], $first->map(['foo' => 1, 'bar' => 0], 1));
    }
    
    public function test_ToString_can_merge_with_other_ToString_mapper(): void
    {
        $first = Mappers::toString('foo');
        $second = Mappers::toString('bar');
    
        self::assertTrue($first->mergeWith($second));
    
        self::assertSame(['foo' => '1', 'bar' => '0'], $first->map(['foo' => 1, 'bar' => 0], 1));
    }
    
    public function test_ToArray_changes_every_argument_into_array(): void
    {
        $withoutKey = Mappers::toArray();
        
        self::assertSame(['a'], $withoutKey->map('a', 1));
        self::assertSame([4], $withoutKey->map(4, 1));
        self::assertSame(['foo' => 'bar'], $withoutKey->map(['foo' => 'bar'], 1));
        self::assertSame(['foo' => 'bar'], $withoutKey->map(new \ArrayObject(['foo' => 'bar']), 1));
        
        $withKey = Mappers::toArray(true);
        
        self::assertSame([1 => 'a'], $withKey->map('a', 1));
        self::assertSame([1 => 4], $withKey->map(4, 1));
        self::assertSame(['foo' => 'bar'], $withKey->map(['foo' => 'bar'], 1));
        self::assertSame(['foo' => 'bar'], $withKey->map(new \ArrayObject(['foo' => 'bar']), 1));
    }
    
    public function test_Shuffle_can_mix_elements_in_arrays(): void
    {
        $array = \range(1, 1000);
        
        self::assertNotSame($array, Mappers::shuffle()->map($array, 0));
    }
    
    public function test_Shuffle_can_mix_chars_in_strings(): void
    {
        $string = \str_repeat(\implode('', \range('a', 'z')), 10);
        
        self::assertNotSame($string, Mappers::shuffle()->map($string, 0));
    }
    
    public function test_Shuffle_can_transform_iterator_into_array_with_mixed_elements(): void
    {
        $array = \range(1, 1000);
        $iterator = new \ArrayIterator($array);
        
        self::assertNotSame($array, Mappers::getAdapter('\shuffle')->map($iterator, 0));
    }
    
    public function test_Filter_used_as_mapper_throws_exception_when_mapped_value_is_not_iterable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to map integer using Filter because it is not iterable');
        
        Mappers::getAdapter(Filters::string()->contains('foo'))->map(15, 1);
    }
    
    public function test_Filter_used_as_mapper_allows_to_remove_elements_in_arrays(): void
    {
        $data = ['a', 1, 'b', 2, 'c', 3];
        
        self::assertSame([1 => 1, 3 => 2, 5 => 3], Mappers::getAdapter(Filters::isInt())->map($data, 1));
    }
    
    public function test_Predicate_used_as_mapper_throws_exception_when_mapped_value_is_not_iterable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to map integer using Predicate because it is not iterable');
        
        Mappers::getAdapter(Predicates::value('foo'))->map(15, 1);
    }
    
    public function test_Predicate_used_as_mapper_allows_to_remove_elements_in_arrays(): void
    {
        $data = ['a', 1, 'b', 2, 'c', 3];
        
        self::assertSame([1 => 1, 3 => 2, 5 => 3], Mappers::getAdapter(Predicates::inArray([1, 2, 3]))->map($data, 1));
    }
    
    public function test_FieldValue_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
        
        Mappers::fieldValue(false);
    }
    
    public function test_FieldValue_throws_exception_when_mapped_value_is_not_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It is impossible to extract field foo from int');
        
        $mapper = Mappers::fieldValue('foo');
        $mapper->map(5, 2);
    }
    
    public function test_FieldValue_throws_exception_when_value_of_field_is_null(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot extract value of field foo');
    
        $mapper = Mappers::fieldValue('foo');
        $mapper->map(['bar' => 3], 2);
    }
    
    public function test_FieldValue_allows_to_merge_only_with_other_FieldValue_mapper(): void
    {
        $first = Mappers::fieldValue('foo');
        $second = Mappers::fieldValue('bar');
        
        self::assertTrue($first->mergeWith($second));
        self::assertFalse($first->mergeWith(Mappers::simple('zoo')));
        
        self::assertSame(2, $first->map(['foo' => 1, 'bar' => 2], 0));
    }
    
    public function test_Value_simply_returns_value(): void
    {
        self::assertSame('foo', Mappers::value()->map('foo', 1));
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param pattern - cannot be empty!');
        
        Mappers::getAdapter([]);
    }
    
    public function test_Replace(): void
    {
        $mapper = Mappers::replace(' ', '');
        
        self::assertSame('abc', $mapper->map('a b c', 0));
    }
    
    public function test_Replace_throws_exception_on_wrong_type_of_param_search(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param search - it cannot be integer');
        
        Mappers::replace(5, '');
    }
    
    public function test_Replace_throws_exception_on_wrong_type_of_param_replace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param replace - it cannot be integer');
        
        Mappers::replace(' ', 5);
    }
    
    public function test_Replace_throws_exception_when_param_search_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param search');
        
        Mappers::replace('', '');
    }
    
    public function test_Replace_throws_exception_when_mapped_value_is_not_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to replace chars in integer');
        
        Mappers::replace(' ', '')->map(15, 0);
    }
    
    public function test_DiscriminatorAdapter_throws_exception_when_Discriminator_returns_invalid_value(): void
    {
        //Assert
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported value was returned from discriminator (got array)');
        
        //Arrange
        $mapper = Mappers::getAdapter(Discriminators::generic(static fn($v): array => [$v]));
        
        //Act
        $mapper->map('foo', 1);
    }
    
    public function test_FieldValue_can_handle_ArrayAccess_implementations(): void
    {
        self::assertSame('a', Mappers::fieldValue('foo')->map(new \ArrayObject(['foo' => 'a']), 1));
    }
    
    public function test_MapField_can_handle_ArrayAccess_implementations(): void
    {
        self::assertEquals(
            new \ArrayObject(['foo' => 6]),
            Mappers::mapField('foo', static fn(int $v): int => $v * 2)->map(new \ArrayObject(['foo' => 3]), 1)
        );
    }
}