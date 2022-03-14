<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Reducer\Max;
use FiiSoft\Jackdaw\Reducer\Min;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\TestCase;

final class ReducersTest extends TestCase
{
    public function test_getAdapter_throws_exception_when_arg_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Reducers::getAdapter(15);
    }
    
    public function test_getAdapter_returns_Reducer_instance_for_some_functions(): void
    {
        self::assertInstanceOf(Min::class, Reducers::getAdapter('min'));
        self::assertInstanceOf(Max::class, Reducers::getAdapter('max'));
    }
    
    public function test_Concat_reducer_accumulates_simple_values_as_string(): void
    {
        $reducer = Reducers::concat();
        $reducer->reset();
        
        foreach ([4, 'a', 2, '5', 'z'] as $value) {
            $reducer->consume($value);
        }
        
        if ($reducer->hasResult()) {
            self::assertSame('4a25z', $reducer->getResult()->value);
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_Shortest_reducer_returns_shortest_string_from_iterable_value(): void
    {
        $reducer = Reducers::shortest();
        $reducer->reset();
    
        foreach (['this', 'is', 'some', 'sentence'] as $word) {
            $reducer->consume($word);
        }
    
        if ($reducer->hasResult()) {
            self::assertSame('is', $reducer->getResult()->value);
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_Longest_reducer_returns_longest_string_from_iterable_value(): void
    {
        $reducer = Reducers::longest();
        $reducer->reset();
    
        foreach (['this', 'is', 'some', 'sentence'] as $word) {
            $reducer->consume($word);
        }
    
        if ($reducer->hasResult()) {
            self::assertSame('sentence', $reducer->getResult()->value);
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_Min_reducer_returns_min_number_from_iterable_value(): void
    {
        $reducer = Reducers::min();
        $reducer->reset();
    
        foreach ([6, 3, 8, 2, 5] as $number) {
            $reducer->consume($number);
        }
    
        if ($reducer->hasResult()) {
            self::assertSame(2, $reducer->getResult()->value);
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_Max_reducer_returns_max_number_from_iterable_value(): void
    {
        $reducer = Reducers::max();
        $reducer->reset();
    
        foreach ([6, 3, 8, 2, 5] as $number) {
            $reducer->consume($number);
        }
    
        if ($reducer->hasResult()) {
            self::assertSame(8, $reducer->getResult()->value);
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_Average_reducer_returns_average_value_from_numbers(): void
    {
        $reducer = Reducers::average(2);
        $reducer->reset();
    
        foreach ([6, 3, 8, 2, 5] as $number) {
            $reducer->consume($number);
        }
    
        if ($reducer->hasResult()) {
            self::assertSame(4.8, $reducer->getResult()->value);
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_callable_has_to_accept_two_arguments(): void
    {
        $reducer = Reducers::getAdapter(static fn($acc, $v): string => $acc.'_'.$v);
        $reducer->reset();
    
        foreach ([4, 'a', 2, '5', 'z'] as $value) {
            $reducer->consume($value);
        }
    
        if ($reducer->hasResult()) {
            self::assertSame('4_a_2_5_z', $reducer->getResult()->value);
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_GenericReducer_throws_exception_when_callable_requires_invalid_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Reducer have to accept 2 arguments, but requires 1');
        
        Reducers::getAdapter('strtolower');
    }
}