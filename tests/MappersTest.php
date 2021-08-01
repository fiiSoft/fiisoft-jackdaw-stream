<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Mapper\Mappers;
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
    
    public function test_toString(): void
    {
        self::assertSame('15', Mappers::toString()->map(15, 1));
    }
    
    public function test_toFloat(): void
    {
        self::assertSame(15.45, Mappers::toFloat()->map('15.45', 1));
    }
    
    public function test_toBool(): void
    {
        self::assertTrue(Mappers::toBool()->map(1, 1));
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
}