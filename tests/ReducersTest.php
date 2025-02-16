<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Reducer\Exception\ReducerExceptionFactory;
use FiiSoft\Jackdaw\Reducer\Max;
use FiiSoft\Jackdaw\Reducer\Min;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\TestCase;

final class ReducersTest extends TestCase
{
    public function test_getAdapter_throws_exception_when_arg_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('reducer'));
        
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
            self::assertSame('4a25z', $reducer->result());
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
            self::assertSame('is', $reducer->result());
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
            self::assertSame('sentence', $reducer->result());
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
            self::assertSame(2, $reducer->result());
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
            self::assertSame(8, $reducer->result());
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
            self::assertSame(4.8, $reducer->result());
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
            self::assertSame('4_a_2_5_z', $reducer->result());
        } else {
            self::fail('Reducer has no result!');
        }
    }
    
    public function test_GenericReducer_throws_exception_when_callable_requires_invalid_number_of_arguments(): void
    {
        $this->expectExceptionObject(ReducerExceptionFactory::invalidParamReducer(1));
        
        Reducers::getAdapter('strtolower');
    }
    
    public function test_MultiReducer_throws_exception_when_initial_array_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('pattern'));
        
        Reducers::getAdapter([]);
    }
    
    public function test_CountUnique_can_count_bool_values(): void
    {
        $reducer = Reducers::countUnique();
        
        $reducer->consume(true);
        $reducer->consume(false);
        $reducer->consume(true);
        
        self::assertSame(['true' => 2, 'false' => 1], $reducer->result());
    }
    
    public function test_CountUnique_can_count_float_values(): void
    {
        $reducer = Reducers::countUnique();
        
        $reducer->consume(4.0);
        $reducer->consume(5.5);
        $reducer->consume(4.0);
        
        self::assertSame([4 => 2, '5.5' => 1], $reducer->result());
    }
    
    public function test_CountUnique_can_count_arrays(): void
    {
        $reducer = Reducers::countUnique();
        
        $arr1 = ['a' => 5];
        $arr2 = [3 => '8'];
        
        $reducer->consume($arr1);
        $reducer->consume($arr2);
        $reducer->consume($arr1);
        
        self::assertSame([
            '9b574798ab3bf7351f82eaf817c96d50' => 2,
            '7d501c92edb4f260b35c33f2825a6703' => 1,
        ], $reducer->result());
    }
    
    public function test_CountUnique_can_count_objects(): void
    {
        $reducer = Reducers::countUnique();
        
        $ob1 = new \stdClass();
        $ob2 = new \stdClass();
        
        $reducer->consume($ob1);
        $reducer->consume($ob2);
        $reducer->consume($ob1);
        
        [$count1, $count2] = \array_values($reducer->result());
     
        self::assertSame(2, $count1);
        self::assertSame(1, $count2);
    }
    
    public function test_CountUnique_can_count_nulls_ints_and_strings(): void
    {
        $reducer = Reducers::countUnique();
        
        $reducer->consume('foo');
        $reducer->consume(null);
        $reducer->consume(15);
        
        self::assertSame(['foo' => 1, 'null' => 1, 15 => 1], $reducer->result());
    }
    
    public function test_CountUnique_can_also_count_other_strange_things(): void
    {
        $reducer = Reducers::countUnique();
        
        $res1 = \fopen('php://stdin', 'rb+');
        $res2 = \fopen('php://memory', 'rb+');
        
        $reducer->consume($res1);
        $reducer->consume($res2);
        $reducer->consume($res1);
        
        [$count1, $count2] = \array_values($reducer->result());
     
        self::assertSame(2, $count1);
        self::assertSame(1, $count2);
    }
    
    public function test_CountUnique_reset(): void
    {
        $reducer = Reducers::countUnique();
        
        $reducer->consume('foo');
        $reducer->reset();
        
        $reducer->consume('bar');
        self::assertSame(['bar' => 1], $reducer->result());
    }
    
    public function test_CountUnique_can_use_provided_discriminator(): void
    {
        $reducer = Reducers::countUnique(Discriminators::byField('name'));
        
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 22],
            ['id' => 9, 'name' => 'Chris', 'age' => 17],
            ['id' => 6, 'name' => 'Joanna', 'age' => 15],
            ['id' => 5, 'name' => 'Chris', 'age' => 24],
            ['id' => 7, 'name' => 'Sue', 'age' => 18],
        ];
        
        foreach ($rowset as $row) {
            $reducer->consume($row);
        }
        
        self::assertSame([
            'Sue' => 2,
            'Chris' => 2,
            'Joanna' => 1,
        ], $reducer->result());
    }
    
    public function test_CountUnique_can_use_boolean_discriminator(): void
    {
        $reducer = Reducers::countUnique(Filters::isString());
        
        foreach (['foo', 15, 'bar'] as $value) {
            $reducer->consume($value);
        }
        
        self::assertSame([1 => 2, 0 => 1], $reducer->result());
    }
}