<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Mapper\Concat;
use FiiSoft\Jackdaw\Mapper\Internal\ReducerAdapter;
use FiiSoft\Jackdaw\Mapper\JsonDecode;
use FiiSoft\Jackdaw\Mapper\JsonEncode;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Reverse;
use FiiSoft\Jackdaw\Mapper\Split;
use FiiSoft\Jackdaw\Mapper\ToBool;
use FiiSoft\Jackdaw\Mapper\ToFloat;
use FiiSoft\Jackdaw\Mapper\ToInt;
use FiiSoft\Jackdaw\Mapper\ToString;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\TestCase;
use stdClass;

final class MappersTest extends TestCase
{
    public function test_Concat_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::concat()->map('string', 1);
    }
    
    public function test_Extract_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::extract([]);
    }
    
    public function test_Extract_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::extract(['field'])->map('not_an_array', 1);
    }
    
    public function test_GenericMapper_throws_exception_when_callable_does_not_accept_required_num_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::generic(static fn() => true)->map('a', 1);
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
    
    public function test_toString_can_map_simple_value_and_also_fileds_in_arrays(): void
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
    
    public function test_toBool_can_map_simple_value_and_also_fileds_in_arrays(): void
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
        self::assertSame(['name' => 'Joe'], $mapper->map($value, 1));
    }
    
    public function test_Remove_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\LogicException::class);
        
        Mappers::remove('id')->map(new stdClass(), 1);
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
        } catch (\LogicException $e) {
            //ok
        }
        
        try {
            Mappers::toInt('id')->map(15, 1);
            self::fail('Exception expected');
        } catch (\LogicException $e) {
            //ok
        }
        
        self::assertTrue(true);
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
        } catch (\LogicException $e) {
            //ok
        }
        
        try {
            Mappers::toFloat('id')->map(15, 1);
            self::fail('Exception expected');
        } catch (\LogicException $e) {
            //ok
        }
        
        self::assertTrue(true);
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
    
    public function test_Append_replaces_only_null_or_missing_keys(): void
    {
        $mapper = Mappers::complete('name', 'anonymous');
        
        self::assertSame(['id' => 3, 'name' => 'Ole'], $mapper->map(['id' => 3, 'name' => 'Ole'], 0));
        self::assertSame(['id' => 3, 'name' => 'anonymous'], $mapper->map(['id' => 3, 'name' => null], 0));
        self::assertSame(['id' => 3, 'name' => 'anonymous'], $mapper->map(['id' => 3], 0));
    }
    
    public function test_MoveTo_creates_array_with_key_from_value(): void
    {
        $mapper = Mappers::moveTo('key');
        
        self::assertSame(['key' => 15], $mapper->map(15, 3));
        self::assertSame(['key' => [2, 3]], $mapper->map([2, 3], 'a'));
    }
    
    public function test_MoveTo_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
        
        Mappers::moveTo('');
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
        self::assertSame([1, 2, 3, 'sum' => 6], Mappers::append('sum', Reducers::sum())->map([1, 2, 3], 1));
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
}