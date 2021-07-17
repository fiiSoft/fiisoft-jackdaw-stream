<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producers;
use PHPUnit\Framework\TestCase;

final class ProducersTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_wrong_param()
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::getAdapter('wrong_argument');
    }
    
    public function test_RandomInt_generator()
    {
        $producer = Producers::randomInt(1, 500, 10);
        $count = 0;
        
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            self::assertInternalType('integer', $item->value);
            self::assertTrue($item->value >= 1);
            self::assertTrue($item->value <= 500);
            ++$count;
        }
        
        self::assertSame(10, $count);
    }
    
    public function test_SequentialInt_generator()
    {
        $producer = Producers::sequentialInt(1, 2, 5);
        $buffer = [];
        
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            $buffer[] = $item->value;
        }
    
        self::assertSame([1,3,5,7,9], $buffer);
    }
    
    public function test_RandomString_geneartor()
    {
        $producer = Producers::randomString(3, 10, 5);
        $count = 0;
    
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            self::assertInternalType('string', $item->value);
            self::assertTrue(\strlen($item->value) >= 3);
            self::assertTrue(\strlen($item->value) <= 10, 'length is '.\strlen($item->value));
            ++$count;
        }
    
        self::assertSame(5, $count);
    }
    
    public function test_RandomUuid_generator()
    {
        if (!\class_exists('\Ramsey\Uuid\Uuid')) {
            self::markTestSkipped('Class Ramsey\Uuid\Uuid is required to run this test');
        }
        
        $producer = Producers::randomUuid(true, 5);
        $count = 0;
    
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            self::assertInternalType('string', $item->value);
            self::assertSame(32, \strlen($item->value));
            self::assertStringMatchesFormat('%x', $item->value);
            ++$count;
        }
        
        self::assertSame(5, $count);
    }
    
    public function test_SequentialInt_generator_throws_exception_on_param_step_zero()
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::sequentialInt(1, 0, 10);
    }
    
    public function test_SequentialInt_generator_throws_exception_on_invalid_param_limit()
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::sequentialInt(1, 1, -1);
    }
    
    public function test_RandomString_throws_exception_on_invalid_limit()
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomString(1, 10, -1);
    }
    
    public function test_RandomString_throws_exception_when_maxLength_is_less_than_minLength()
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomString(11, 10, 1);
    }
    
    public function test_RandomString_can_generate_string_of_const_length()
    {
        $producer = Producers::randomString(5, 5, 3);
        $item = new Item();
    
        foreach ($producer->feed($item) as $_) {
            self::assertSame(5, \strlen($item->value));
        }
    }
    
    public function test_RandomInt_throws_exception_on_invalid_limit()
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomInt(1, 2, -1);
    }
    
    public function test_RandomInt_thows_exception_when_max_is_not_greater_than_min()
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomInt(2, 2);
    }
}