<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\TestCase;

final class SequenceMemoTest extends TestCase
{
    public function test_it_throws_exception_when_length_is_less_then_two(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('length'));

        $this->memoOfLength(1);
    }
    
    public function test_empty_memo_contains_zero_elements(): void
    {
        self::assertSame(0, $this->memoOfLength(2)->count());
    }
    
    public function test_empty_memo_is_empty(): void
    {
        self::assertTrue($this->memoOfLength(3)->isEmpty());
    }
    
    public function test_count_returns_number_of_currently_stored_elements(): void
    {
        //given
        $memo = $this->memoOfLength(2);
        
        //when
        $memo->write('a', 4);
        
        //then
        self::assertSame(1, $memo->count());
        
        //when
        $memo->write('b', 1);
        
        //then
        self::assertSame(2, $memo->count());
    }
    
    public function test_number_of_stored_elements_nerver_exceeds_set_length_of_sequence(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $memo->write('a', 1);
        
        //then
        self::assertSame(3, $memo->count());
    }
    
    public function test_memo_with_at_least_one_stored_element_is_not_empty(): void
    {
        //given
        $memo = $this->memoOfLength(3);
        
        //when
        $memo->write('a', 4);
        
        //then
        self::assertFalse($memo->isEmpty());
    }
    
    public function test_it_can_tell_when_is_full(): void
    {
        $memo = $this->memoOfLength(2);
        self::assertFalse($memo->isFull());
        
        $memo->write('a', 4);
        self::assertFalse($memo->isFull());
        
        $memo->write('b', 2);
        self::assertTrue($memo->isFull());
    }
    
    public function test_infinite_sequence_memo_is_never_full(): void
    {
        $memo = Memo::sequence();
        
        $memo->write('a', 4);
        
        self::assertFalse($memo->isFull());
    }
    
    public function test_it_can_return_all_collected_elements_as_array(): void
    {
        $memo = $this->filledSequenceOfLength3();
        
        self::assertSame([4 => 'a', 3 => 'b', 2 => 'c'], $memo->toArray());
    }
    
    public function test_empty_sequence_returns_empty_array(): void
    {
        self::assertSame([], $this->memoOfLength(2)->toArray());
    }
    
    public function test_it_can_remove_any_element_counting_from_the_beginning(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $removed = $memo->remove(1);
        
        //then
        self::assertTrue($removed->is(3, 'b'));
        self::assertSame(2, $memo->count());
        self::assertSame([4 => 'a', 2 => 'c'], $memo->toArray());
    }
    
    public function test_it_can_remove_any_element_counting_from_the_end(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $removed = $memo->remove(-1);
        
        //then
        self::assertTrue($removed->is(2, 'c'));
        self::assertSame(2, $memo->count());
        self::assertSame([4 => 'a', 3 => 'b'], $memo->toArray());
    }
    
    public function test_it_can_return_particular_element_as_simple_array_of_key_and_value(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $first = $memo->pair(0);
        $second = $memo->pair(1);
        $third = $memo->pair(2);
        
        //then
        self::assertSame([4 => 'a'], $first->read());
        self::assertSame([3 => 'b'], $second->read());
        self::assertSame([2 => 'c'], $third->read());
    }
    
    public function test_it_can_return_particular_element_as_tuple_with_key_and_array(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $first = $memo->tuple(0);
        $second = $memo->tuple(1);
        $third = $memo->tuple(2);
        
        //then
        self::assertSame([4, 'a'], $first->read());
        self::assertSame([3, 'b'], $second->read());
        self::assertSame([2, 'c'], $third->read());
    }
    
    public function test_it_can_return_element_as_pair_counting_from_the_end(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $firstFromTheEnd = $memo->pair(-1);
        $secondFromTheEnd = $memo->pair(-2);
        $thirdFromTheEnd = $memo->pair(-3);
        
        //then
        self::assertSame([2 => 'c'], $firstFromTheEnd->read());
        self::assertSame([3 => 'b'], $secondFromTheEnd->read());
        self::assertSame([4 => 'a'], $thirdFromTheEnd->read());
    }
    
    public function test_it_can_return_element_as_tuple_counting_from_the_end(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $firstFromTheEnd = $memo->tuple(-1);
        $secondFromTheEnd = $memo->tuple(-2);
        $thirdFromTheEnd = $memo->tuple(-3);
        
        //then
        self::assertSame([2, 'c'], $firstFromTheEnd->read());
        self::assertSame([3, 'b'], $secondFromTheEnd->read());
        self::assertSame([4, 'a'], $thirdFromTheEnd->read());
    }
    
    public function test_it_can_return_value_of_particular_element(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $firstValue = $memo->value(0);
        $lastValue = $memo->value(-1);
        
        //then
        self::assertSame('a', $firstValue->read());
        self::assertSame('c', $lastValue->read());
    }
    
    public function test_it_can_return_key_of_particular_element(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $firstKey = $memo->key(0);
        $lastKey = $memo->key(-1);
        
        //then
        self::assertSame(4, $firstKey->read());
        self::assertSame(2, $lastKey->read());
    }

    public function test_it_allows_to_clear_out_from_all_of_collected_elements(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $memo->clear();
        
        //then
        self::assertSame([], $memo->toArray());
        self::assertSame(0, $memo->count());
        self::assertFalse($memo->isFull());
        self::assertTrue($memo->isEmpty());
    }

    public function test_it_allows_to_return_particular_element_from_the_sequence(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $first = $memo->get(0);
        $last = $memo->get(-1);
        
        //then
        self::assertSame('a', $first->value);
        self::assertSame(4, $first->key);
        
        self::assertEquals('c', $last->value);
        self::assertEquals(2, $last->key);
    }
    
    public function test_it_can_return_value_of_particular_element_directly(): void
    {
        self::assertSame('c', $this->filledSequenceOfLength3()->valueOf(-1));
    }
    
    public function test_it_can_return_key_of_particular_element_directly(): void
    {
        self::assertSame(4, $this->filledSequenceOfLength3()->keyOf(0));
    }
    
    public function test_it_is_iterable(): void
    {
        $memo = $this->memoOfLength(2);
        
        foreach ($memo as $value) {
            self::fail('Empty memo should not produce any values');
        }
        
        $memo->write('a', 1);
        $memo->write('b', 2);
        
        $result = [];
        foreach ($memo as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame([1 => 'a', 2 => 'b'], $result);
    }
    
    public function test_it_provides_fold_operation(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $folded = $memo->fold('', static fn(string $acc, string $value, int $key): string => $acc.$value.$key);
        
        //then
        self::assertSame('a4b3c2', $folded);
    }
    
    public function test_it_provides_reduce_operation(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $reduced = $memo->reduce(static fn(string $acc, string $value): string => $acc.$value);
        
        //then
        self::assertSame('abc', $reduced);
    }
    
    public function test_sequence_can_be_streamed(): void
    {
        //given
        $memo = $this->filledSequenceOfLength3();
        
        //when
        $result = $memo->stream()->flip()->reduce(Reducers::sum())->get();
        
        //then
        self::assertSame(9, $result);
    }
    
    private function filledSequenceOfLength3(): SequenceMemo
    {
        $memo = $this->memoOfLength(3);
        
        $memo->write('a', 4);
        $memo->write('b', 3);
        $memo->write('c', 2);
        
        return $memo;
    }
    
    private function memoOfLength(int $length): SequenceMemo
    {
        return Memo::sequence($length);
    }
}