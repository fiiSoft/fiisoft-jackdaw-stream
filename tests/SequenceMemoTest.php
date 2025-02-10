<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Matcher\MatchBy;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\TestCase;

final class SequenceMemoTest extends TestCase
{
    private const SEQ_DATA = [4 => 'a', 3 => 'b', 2 => 'c'];
    
    public function test_limited_sequence_throws_exception_when_length_is_less_then_one(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('length'));

        $this->sequence(0);
    }
    
    public function test_empty_sequence_contains_zero_elements(): void
    {
        self::assertSame(0, $this->sequence(2)->count());
    }
    
    public function test_empty_sequence_is_empty(): void
    {
        self::assertTrue($this->sequence(3)->isEmpty());
    }
    
    public function test_empty_sequence_returns_empty_array(): void
    {
        self::assertSame([], $this->sequence(2)->toArray());
    }
    
    public function test_count_returns_number_of_currently_stored_elements(): void
    {
        //given
        $sequence = $this->sequence(2);
        
        //when
        $sequence->write('a', 4);
        
        //then
        self::assertSame(1, $sequence->count());
        
        //when
        $sequence->write('b', 1);
        
        //then
        self::assertSame(2, $sequence->count());
    }
    
    public function test_number_of_stored_elements_never_exceeds_set_length_of_sequence(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $sequence->write('a', 1);
        
        //then
        self::assertSame(3, $sequence->count());
    }
    
    public function test_sequence_with_at_least_one_stored_element_is_not_empty(): void
    {
        //given
        $sequence = $this->sequence(3);
        
        //when
        $sequence->write('a', 4);
        
        //then
        self::assertFalse($sequence->isEmpty());
    }
    
    public function test_limited_sequence_can_tell_when_is_full(): void
    {
        $sequence = $this->sequence(2);
        self::assertFalse($sequence->isFull());
        
        $sequence->write('a', 4);
        self::assertFalse($sequence->isFull());
        
        $sequence->write('b', 2);
        self::assertTrue($sequence->isFull());
    }
    
    public function test_infinite_sequence_memo_is_never_full(): void
    {
        //given
        $sequence = $this->sequence();
        
        //when
        $sequence->write('a', 4);
        
        //then
        self::assertFalse($sequence->isFull());
    }
    
    public function test_sequence_can_return_all_collected_elements_as_array(): void
    {
        self::assertSame(self::SEQ_DATA, $this->limitedSequence()->toArray());
    }
    
    public function test_limited_sequence_allows_to_remove_any_element_counting_from_head(): void
    {
        //given
        $sequence = $this->limitedSequence();
        $sequence->write('d', 1);
        
        self::assertSame([3 => 'b', 2 => 'c', 1 => 'd'], $sequence->toArray());
        self::assertSame(3, $sequence->count());
        
        //when
        $removed = $sequence->remove(1);
        
        //then
        self::assertTrue($removed->is(2, 'c'));
        self::assertSame(2, $sequence->count());
        self::assertSame([3 => 'b', 1 => 'd'], $sequence->toArray());
        
        //when
        $removed = $sequence->remove(1);
        
        //then
        self::assertTrue($removed->is(1, 'd'));
        self::assertSame(1, $sequence->count());
        self::assertSame([3 => 'b'], $sequence->toArray());
        
        //when
        $removed = $sequence->remove(0);
        
        //then
        self::assertTrue($removed->is(3, 'b'));
        self::assertSame(0, $sequence->count());
        self::assertSame([], $sequence->toArray());
        
        //when
        $this->fillSequence($sequence);
        
        //then
        self::assertSame(self::SEQ_DATA, $sequence->toArray());
        self::assertSame(3, $sequence->count());
    }
    
    public function test_limited_sequence_can_remove_the_last_element_counting_from_head(): void
    {
        //given
        $sequence = $this->limitedSequence();
        $sequence->write('d', 1);
        
        //when
        $removed = $sequence->remove(2);
        
        //then
        self::assertTrue($removed->is(1, 'd'));
        self::assertSame(2, $sequence->count());
        self::assertSame([3 => 'b', 2 => 'c'], $sequence->toArray());
    }
    
    public function test_limited_sequence_can_remove_any_element_counting_from_tail(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $removed = $sequence->remove(-1);
        
        //then
        self::assertTrue($removed->is(2, 'c'));
        self::assertSame(2, $sequence->count());
        self::assertSame([4 => 'a', 3 => 'b'], $sequence->toArray());
    }
    
    public function test_limited_sequence_not_full_yet_allows_to_remove_elements(): void
    {
        //given
        $sequence = $this->sequence(3);
        
        $sequence->write('a', 1);
        $sequence->write('b', 2);
        
        //when
        $removed = $sequence->remove(-2);
        
        //then
        self::assertTrue($removed->is(1, 'a'));
        self::assertSame([2 => 'b'], $sequence->toArray());
    }
    
    public function test_sequence_can_return_particular_element_as_simple_array_of_key_and_value(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $first = $sequence->pair(0);
        $second = $sequence->pair(1);
        $third = $sequence->pair(2);
        
        //then
        self::assertSame([4 => 'a'], $first->read());
        self::assertSame([3 => 'b'], $second->read());
        self::assertSame([2 => 'c'], $third->read());
    }
    
    public function test_sequence_can_return_particular_element_as_tuple_with_key_and_array(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $first = $sequence->tuple(0);
        $second = $sequence->tuple(1);
        $third = $sequence->tuple(2);
        
        //then
        self::assertSame([4, 'a'], $first->read());
        self::assertSame([3, 'b'], $second->read());
        self::assertSame([2, 'c'], $third->read());
    }
    
    public function test_sequence_can_return_element_as_pair_counting_from_the_end(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $firstFromTheEnd = $sequence->pair(-1);
        $secondFromTheEnd = $sequence->pair(-2);
        $thirdFromTheEnd = $sequence->pair(-3);
        
        //then
        self::assertSame([2 => 'c'], $firstFromTheEnd->read());
        self::assertSame([3 => 'b'], $secondFromTheEnd->read());
        self::assertSame([4 => 'a'], $thirdFromTheEnd->read());
    }
    
    public function test_sequence_can_return_element_as_tuple_counting_from_the_end(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $firstFromTheEnd = $sequence->tuple(-1);
        $secondFromTheEnd = $sequence->tuple(-2);
        $thirdFromTheEnd = $sequence->tuple(-3);
        
        //then
        self::assertSame([2, 'c'], $firstFromTheEnd->read());
        self::assertSame([3, 'b'], $secondFromTheEnd->read());
        self::assertSame([4, 'a'], $thirdFromTheEnd->read());
    }
    
    public function test_sequence_can_return_value_reader_of_particular_element(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $firstValue = $sequence->value(0);
        $lastValue = $sequence->value(-1);
        
        //then
        self::assertSame('a', $firstValue->read());
        self::assertSame('c', $lastValue->read());
    }
    
    public function test_sequence_can_return_key_reader_of_particular_element(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $firstKey = $sequence->key(0);
        $lastKey = $sequence->key(-1);
        
        //then
        self::assertSame(4, $firstKey->read());
        self::assertSame(2, $lastKey->read());
    }

    public function test_sequence_allows_to_clear_all_of_its_elements(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $sequence->clear();
        
        //then
        self::assertSame([], $sequence->toArray());
        self::assertSame(0, $sequence->count());
        self::assertFalse($sequence->isFull());
        self::assertTrue($sequence->isEmpty());
    }

    public function test_sequence_allows_to_return_particular_element(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $first = $sequence->get(0);
        $last = $sequence->get(-1);
        
        //then
        self::assertSame('a', $first->value);
        self::assertSame(4, $first->key);
        
        self::assertEquals('c', $last->value);
        self::assertEquals(2, $last->key);
    }
    
    public function test_limited_sequence_can_return_value_of_particular_element_directly(): void
    {
        self::assertSame('c', $this->limitedSequence()->valueOf(-1));
    }
    
    public function test_limited_sequence_can_return_key_of_particular_element_directly(): void
    {
        self::assertSame(4, $this->limitedSequence()->keyOf(0));
    }
    
    public function test_sequence_is_iterable(): void
    {
        $sequence = $this->sequence(2);
        
        foreach ($sequence as $value) {
            self::fail('Empty memo should not produce any values');
        }
        
        $sequence->write('a', 1);
        $sequence->write('b', 2);
        
        self::assertSame([1 => 'a', 2 => 'b'], \iterator_to_array($sequence));
    }
    
    public function test_limited_sequence_provides_fold_operation(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $folded = $sequence->fold('*', static fn(string $acc, string $value, int $key): string => $acc.$value.$key);
        
        //then
        self::assertSame('*a4b3c2', $folded);
    }
    
    public function test_infinite_sequence_provides_fold_operation(): void
    {
        //given
        $sequence = $this->infiniteSequence();
        
        //when
        $folded = $sequence->fold('*', static fn(string $acc, string $value, int $key): string => $acc.$value.$key);
        
        //then
        self::assertSame('*a4b3c2', $folded);
    }
    
    public function test_limited_sequence_provides_reduce_operation(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $reduced = $sequence->reduce(static fn(string $acc, string $value): string => $acc.$value);
        
        //then
        self::assertSame('abc', $reduced);
    }
    
    public function test_sequence_can_be_streamed(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $result = $sequence->stream()->flip()->reduce(Reducers::sum())->get();
        
        //then
        self::assertSame(9, $result);
    }
    
    public function test_sequence_matches_compare_values_1(): void
    {
        $this->examineSequenceByValues(null);
    }
    
    public function test_sequence_matches_compare_values_2(): void
    {
        $this->examineSequenceByValues(MatchBy::values());
    }
    
    public function test_sequence_matches_compare_values_3(): void
    {
        $this->examineSequenceByValues(static fn($a, $b): bool => $a === $b);
    }
    
    public function test_sequence_matches_compare_values_4(): void
    {
        $this->examineSequenceByValues(MatchBy::values(static fn($a, $b): bool => $a === $b));
    }
    
    public function test_sequence_matches_compare_values_5(): void
    {
        $this->examineSequenceByValues(static fn($a, $b): int => $a <=> $b);
    }
    
    private function examineSequenceByValues($matcher): void
    {
        $sequence = $this->limitedSequence();
        
        self::assertFalse($sequence->matches([], $matcher)->evaluate());
        self::assertFalse($sequence->matches(['a'], $matcher)->evaluate());
        self::assertFalse($sequence->matches(['a', 'b'], $matcher)->evaluate());
        self::assertFalse($sequence->matches(['a', 'b', 'd'], $matcher)->evaluate());
        self::assertFalse($sequence->matches(['b', 'a', 'c'], $matcher)->evaluate());
        
        self::assertTrue($sequence->matches(['a', 'b', 'c'], $matcher)->evaluate());
    }
    
    public function test_sequence_matches_compare_values_6(): void
    {
        $sequence = $this->sequence(3);
        
        $sequence->write('A', 1);
        $sequence->write('b', 2);
        
        self::assertFalse($sequence->matches(['a', 'b'])->evaluate());
        
        self::assertFalse($sequence->matches(['a', 'c'], '\strcasecmp')->evaluate());
        self::assertFalse($sequence->matches(['a', 'c'], MatchBy::values('\strcasecmp'))->evaluate());
        
        self::assertTrue($sequence->matches(['a', 'B'], '\strcasecmp')->evaluate());
        self::assertTrue($sequence->matches(['a', 'B'], MatchBy::values('\strcasecmp'))->evaluate());
    }
    
    public function test_sequence_matches_compare_keys_1(): void
    {
        $this->examineSequenceByKeys(MatchBy::keys());
    }
    
    public function test_sequence_matches_compare_keys_2(): void
    {
        $this->examineSequenceByKeys(MatchBy::keys(static fn($a, $b): bool => $a === $b));
    }
    
    public function test_sequence_matches_compare_keys_3(): void
    {
        $this->examineSequenceByKeys(MatchBy::keys(static fn($a, $b): int => $a <=> $b));
    }
    
    private function examineSequenceByKeys($matcher): void
    {
        $sequence = $this->limitedSequence();
        
        self::assertFalse($sequence->matches([], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'o'], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'o', 3 => 'o'], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'o', 3 => 'o', 1 => 'o'], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'o', 2 => 'o', 3 => 'o'], $matcher)->evaluate());
        
        self::assertTrue($sequence->matches([4 => 'o', 3 => 'o', 2 => 'o'], $matcher)->evaluate());
    }
    
    public function test_sequence_matches_compare_keys_4(): void
    {
        $sequence = $this->sequence(3);
        
        $sequence->write(1, 'A');
        $sequence->write(2, 'b');
        
        self::assertFalse($sequence->matches(['a' => 0, 'c' => 0], MatchBy::keys('\strcasecmp'))->evaluate());
        self::assertTrue($sequence->matches(['a' => 0, 'B' => 0], MatchBy::keys('\strcasecmp'))->evaluate());
    }
    
    public function test_sequence_matches_compare_both_1(): void
    {
        $this->examineSequenceByValuesAndKeys(MatchBy::both());
    }
    
    public function test_sequence_matches_compare_both_2(): void
    {
        $this->examineSequenceByValuesAndKeys(MatchBy::both(static fn($a, $b): bool => $a === $b));
    }
    
    public function test_sequence_matches_compare_both_3(): void
    {
        $this->examineSequenceByValuesAndKeys(MatchBy::both(static fn($a, $b): int => $a <=> $b));
    }
    
    private function examineSequenceByValuesAndKeys($matcher): void
    {
        $sequence = $this->limitedSequence();
        
        self::assertFalse($sequence->matches([4 => 'a', 3 => 'b', 1 => 'c'], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'a', 2 => 'c', 3 => 'b'], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'a', 3 => 'c', 2 => 'e'], $matcher)->evaluate());
        
        self::assertTrue($sequence->matches(self::SEQ_DATA, $matcher)->evaluate());
    }
    
    public function test_sequence_matches_compare_full_1(): void
    {
        $this->examineSequenceFull(static fn($v1, $v2, $k1, $k2): bool => $v1 === $v2 && $k1 === $k2);
    }
    
    public function test_sequence_matches_compare_full_2(): void
    {
        $this->examineSequenceFull(static fn($v1, $v2, $k1, $k2): int => $v1 <=> $v2 ?: $k1 <=> $k2);
    }
    
    public function test_sequence_matches_compare_full_3(): void
    {
        $this->examineSequenceFull(MatchBy::full(static fn($v1, $v2, $k1, $k2): bool => $v1 === $v2 && $k1 === $k2));
    }
    
    public function test_sequence_matches_compare_full_4(): void
    {
        //int
        $this->examineSequenceFull(MatchBy::full(static fn($v1, $v2, $k1, $k2): int => $v1 <=> $v2 ?: $k1 <=> $k2));
    }
    
    private function examineSequenceFull($matcher): void
    {
        $sequence = $this->limitedSequence();
        
        self::assertFalse($sequence->matches([4 => 'a', 3 => 'b', 1 => 'c'], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'a', 2 => 'c', 3 => 'b'], $matcher)->evaluate());
        self::assertFalse($sequence->matches([4 => 'a', 3 => 'c', 2 => 'e'], $matcher)->evaluate());
        
        self::assertTrue($sequence->matches(self::SEQ_DATA, $matcher)->evaluate());
    }
    
    public function test_empty_sequence_matches(): void
    {
        $sequence = $this->sequence(3);
        
        self::assertTrue($sequence->matches([])->evaluate());
        self::assertFalse($sequence->matches(['a'])->evaluate());
    }
    
    public function test_method_matches_throws_exception_when_matcher_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('matcher'));
        
        $this->limitedSequence()->matches(['a', 'b', 'c'], 15);
    }
    
    public function test_method_matches_throws_exception_when_matcher_requires_unsupported_number_of_args_1(): void
    {
        $this->expectExceptionObject(Helper::wrongNumOfArgsException('Matcher', 1, 2, 4));
        
        $this->limitedSequence()->matches(['a', 'b', 'c'], '\strtolower');
    }
    
    public function test_method_matches_throws_exception_when_matcher_requires_unsupported_number_of_args_2(): void
    {
        $this->expectExceptionObject(Helper::wrongNumOfArgsException('Matcher', 1, 2));
        
        $this->limitedSequence()->matches(['a', 'b', 'c'], MatchBy::values('\strtolower'));
    }
    
    public function test_sequence_throws_exception_when_argument_for_method_inspect_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('inspector'));
        
        $this->limitedSequence()->inspect(15);
    }
    
    public function test_infinite_sequence_memo_allows_to_get_value_and_key_of_stored_elements_directly(): void
    {
        $sequence = $this->sequence();
        
        $sequence->write('a', 1);
        
        self::assertSame('a', $sequence->valueOf(0));
        self::assertSame(1, $sequence->keyOf(0));
        
        self::assertSame('a', $sequence->valueOf(-1));
        self::assertSame(1, $sequence->keyOf(-1));
        
        $sequence->write('b', 2);
        
        self::assertSame('a', $sequence->valueOf(0));
        self::assertSame(1, $sequence->keyOf(0));
        
        self::assertSame('b', $sequence->valueOf(-1));
        self::assertSame(2, $sequence->keyOf(-1));
        
        $sequence->write('c', 3);
        
        self::assertSame('a', $sequence->valueOf(0));
            self::assertSame(1, $sequence->keyOf(0));
        
        self::assertSame('c', $sequence->valueOf(-1));
        self::assertSame(3, $sequence->keyOf(-1));
    }
    
    public function test_infinite_sequence_is_iterable(): void
    {
        $sequence = $this->infiniteSequence();
        
        $result = [];
        foreach ($sequence as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertSame(self::SEQ_DATA, $result);
    }
    
    public function test_sequence_of_length_1_keeps_only_the_last_element(): void
    {
        $sequence = $this->sequence(1);
     
        $sequence->write('a', 1);
        
        self::assertTrue($sequence->get(0)->is(1, 'a'));
        self::assertTrue($sequence->get(-1)->is(1, 'a'));
     
        $sequence->write('b', 2);
        
        self::assertTrue($sequence->get(0)->is(2, 'b'));
        self::assertTrue($sequence->get(-1)->is(2, 'b'));
    }
    
    public function test_sequence_of_length_1_throws_exception_when_index_is_invalid_on_get(): void
    {
        //Assert
        $this->expectExceptionObject(InvalidParamException::byName('index'));
        
        //Arrange
        $sequence = $this->sequence(1);
        $sequence->write('a', 1);
        
        //Act
        $sequence->get(2);
    }
    
    public function test_sequence_of_length_1_allows_to_remove_its_element(): void
    {
        //given
        $sequence = $this->sequence(1);
        $sequence->write('a', 1);
        
        //when
        $removed = $sequence->remove(0);
        
        //then
        self::assertTrue($removed->is(1, 'a'));
        
        self::assertTrue($sequence->isEmpty());
        self::assertFalse($sequence->isFull());
        self::assertSame(0, $sequence->count());
    }
    
    public function test_sequence_of_length_1_throws_exception_when_index_is_invalid_on_remove(): void
    {
        //Assert
        $this->expectExceptionObject(InvalidParamException::byName('index'));
        
        //Arrange
        $sequence = $this->sequence(1);
        $sequence->write('a', 1);
        
        //Act
        $sequence->remove(2);
    }
    
    public function test_sequence_of_length_1_allows_to_remove_hold_element(): void
    {
        //given
        $sequence = $this->sequence(1);
        $sequence->write('a', 1);
        
        //when
        $sequence->clear();
        
        //then
        self::assertTrue($sequence->isEmpty());
        self::assertFalse($sequence->isFull());
        self::assertSame(0, $sequence->count());
    }
    
    public function test_sequence_of_length_1_can_be_destroyed(): void
    {
        //given
        $sequence = $this->sequence(1);
        $sequence->write('a', 1);
        
        //when
        $sequence->destroy();
        
        //then
        self::assertTrue($sequence->isEmpty());
        self::assertFalse($sequence->isFull());
        self::assertSame(0, $sequence->count());
    }
    
    public function test_sequence_of_length_1_is_iterable(): void
    {
        //given
        $sequence = $this->sequence(1);
        $sequence->write('a', 1);
        
        //when
        $result = \iterator_to_array($sequence);
        
        //then
        self::assertSame([1 => 'a'], $result);
    }
    
    public function test_limited_sequence_can_be_destroyed(): void
    {
        //given
        $sequence = $this->limitedSequence();
        
        //when
        $sequence->destroy();
        
        //then
        self::assertTrue($sequence->isEmpty());
        self::assertFalse($sequence->isFull());
        self::assertSame(0, $sequence->count());
    }
    
    public function test_infinite_sequence_can_be_destroyed(): void
    {
        //given
        $sequence = $this->infiniteSequence();
        $sequence->write('a', 1);
        
        //when
        $sequence->destroy();
        
        //then
        self::assertTrue($sequence->isEmpty());
        self::assertFalse($sequence->isFull());
        self::assertSame(0, $sequence->count());
    }
    
    private function limitedSequence(): SequenceMemo
    {
        return $this->fillSequence($this->sequence(3));
    }
    
    private function infiniteSequence(): SequenceMemo
    {
        return $this->fillSequence($this->sequence());
    }
    
    private function fillSequence(SequenceMemo $sequence): SequenceMemo
    {
        foreach (self::SEQ_DATA as $key => $value) {
            $sequence->write($value, $key);
        }
        
        return $sequence;
    }
    
    private function sequence(?int $length = null): SequenceMemo
    {
        return Memo::sequence($length);
    }
}