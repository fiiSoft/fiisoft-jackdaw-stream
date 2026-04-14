<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrototypeATest extends TestCase
{
    public function test_prototype_creates_separate_instances_of_stream(): void
    {
        $s1 = Stream::prototype()->filter(Filters::isString()->or(Filters::isInt()));
        
        $s2 = $s1->join(['a', 'b', false, 'c', 'd', 'e']);
        $s3 = $s2->only(['e', 'y', 'u', 'i', 'o', 'a']);
        $s4 = $s3->map('strtoupper');
        
        $s5 = $s1->join([1, 2, false, 3, 4, 5]);
        $s6 = $s5->filter(Filters::number()->isOdd());
        $s7 = $s5->filter(Filters::number()->isEven());
        $s8 = $s7->reduce(Reducers::sum());
        
        $s9 = $s6->rsort()->limit(2);
        
        self::assertTrue($s1->isEmpty()->get());
        self::assertSame('abcde', $s2->toString(''));
        self::assertSame('ae', $s3->toString(''));
        self::assertSame('AE', $s4->toString(''));
        self::assertSame('12345', $s5->toString(''));
        self::assertSame('135', $s6->toString(''));
        self::assertSame('24', $s7->toString(''));
        self::assertSame('6', $s8->toString(''));
        self::assertSame('53', $s9->toString(''));
        
        self::assertSame([2, 4], $s7->toArray());
        self::assertSame(3, $s6->skip(1)->first()->get());
        self::assertSame('["E","A"]', $s4->reverse()->toJson());
        self::assertSame(3, $s1->wrap([1, true, 2, 3])->count()->get());
        
        $s10 = $s6->omitReps()->collectValues();
        $s10->consume([2, 5, 9, 3, 8, 7, 1, 4]);
        self::assertSame([5, 9, 3, 7, 1, 3, 5], $s10->get());
        
        $s11 = $s2->find('c');
        self::assertTrue($s11->found());
        self::assertSame([3, 'c'], $s11->tuple());
        
        self::assertSame(4, $s9->reduce(Reducers::average())->get());
    }
    
    public function test_prototype_with_group(): void
    {
        $stream = Stream::prototype(['the quick brown fox', 'jumps over the lazy dog'])
            ->flatMap(static fn(string $line): array => \explode(' ', $line), 1)
            ->filter(Filters::length()->between(4, 5))
            ->countIn($countWords)
            ->mapKey('strlen');
        
        //first iteration
        $group1 = $stream->group();
        
        self::assertSame(5, $countWords);
        self::assertSame([5, 4], $group1->classifiers());
        self::assertSame(['over', 'lazy'], $group1->get(4)->get());
        
        //second iteration
        $group2 = $stream->group();
        
        self::assertSame(10, $countWords);
        self::assertSame([5, 4], $group2->classifiers());
        self::assertSame(['quick', 'brown', 'jumps'], $group2->get(5)->get());
    }
    
    public function test_prototype_with_feed(): void
    {
        $sumA = Stream::empty()->filterKey('a')->reduce(Reducers::sum());
        
        $stream = Stream::prototype()
            ->join(['b:5', 'c:3', 'a:8', 'c:2', 'a:1', 'b:2', 'c:2', 'a:4', 'c:5'])
            ->split(':')
            ->unpackTuple()
            ->castToInt()
            ->feed($sumA)
            ->limit(7);
        
        //first iteration
        self::assertSame([
            'b' => [5, 2],
            'c' => [3, 2, 2],
            'a' => [8, 1],
        ], $stream->categorizeByKey()->toArrayAssoc());
        
        self::assertSame(9, $sumA->get());
        
        //second iteration
        self::assertSame([3, 2, 1, 2, 2], $stream->lessThan(5)->toArray());
        
        self::assertSame(18, $sumA->get());
        
        //third iteration
        self::assertSame([5, 3, 8, 2, 1, 2, 2], $stream->toArray());
        
        self::assertSame(27, $sumA->get());
    }
    
    public function test_prototype_with_loop_and_call(): void
    {
        $collatz = Memo::sequence();
        $counter = 0;
        
        $stream = Stream::prototype([3])
            ->call($collatz)
            ->while(Filters::greaterThan(1))
            ->mapWhen(
                static fn(int $n): bool => ($n & 1) === 0,
                static fn (int $n): int => $n >> 1,
                static fn (int $n): int => (3 * $n + 1),
            );
        
        $withCounter = $stream->countIn($counter);
        $withFilter = $stream->filter(Filters::number()->isOdd()->or(Filters::number()->isEven()));
        
        $expected = [3, 10, 5, 16, 8, 4, 2, 1];
        
        //first iteration
        $stream->loop()->run();
        
        self::assertSame(0, $counter);
        self::assertSame(\count($expected), $collatz->count());
        self::assertSame($expected, $collatz->getValues());
        self::assertSame(\array_sum($expected), $collatz->reduce(Reducers::sum()));
        
        //second iteration
        $withCounter->loop(true);
        
        self::assertSame(7, $counter);
        self::assertSame(2 * \count($expected), $collatz->count());
        self::assertSame(2 * \array_sum($expected), $collatz->reduce(Reducers::sum()));
        
        //third iteration
        $counter = 0;
        $withFilter->loop()->run();
        
        self::assertSame(0, $counter);
        self::assertSame(3 * \count($expected), $collatz->count());
        self::assertSame(3 * \array_sum($expected), $collatz->reduce(Reducers::sum()));
    }
    
    public function test_prototype_with_call_counter(): void
    {
        $counter = Consumers::counter();
        $stream = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e', 5])->call($counter);
        
        //first iteration
        self::assertSame('abcde', $stream->onlyStrings()->toString(''));
        
        self::assertSame(10, $counter->get());
        
        //second iteration
        self::assertSame('12345', $stream->onlyIntegers()->toString(''));
        
        self::assertSame(20, $counter->get());
    }
    
    public function test_prototype_with_callOnce_and_callMax(): void
    {
        $once = Consumers::counter();
        $twice = Consumers::counter();
        
        $stream = Stream::prototype()
            ->join(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e', 5])
            ->callOnce($once)
            ->callMax(2, $twice);
        
        //first iteration
        self::assertSame('abcde', $stream->onlyStrings()->toString(''));
        
        self::assertSame(1, $once->get());
        self::assertSame(2, $twice->get());
        
        //second iteration
        self::assertSame(15, $stream->onlyIntegers()->reduce(Reducers::sum())->get());
        
        self::assertSame(2, $once->get());
        self::assertSame(4, $twice->get());
        
        $other = $stream->chunk(2, true)->unpackTuple();
        
        //third iteration
        self::assertSame('abcde', $other->collectKeys()->toString(''));
        
        self::assertSame(3, $once->get());
        self::assertSame(6, $twice->get());
        
        //fourth iteration
        self::assertSame([1, 2, 3, 4, 5], $other->toArray());
        
        self::assertSame(4, $once->get());
        self::assertSame(8, $twice->get());
    }
    
    public function test_prototype_with_route(): void
    {
        $sumInts = Reducers::sum();
        $strings = Collectors::values();
        
        $stream = Stream::prototype()
            ->route('is_string', $strings)
            ->route('is_int', $sumInts)
            ->join(['a', 1, 15.0, 'b', 2, 'c', false, 3, 'd', 25.0]);
        
        //first iteration
        self::assertSame(3, $stream->count()->get());
        
        self::assertSame(6, $sumInts->result());
        self::assertSame('abcd', $strings->toString(''));
        
        //second iteration
        self::assertSame([15.0, 25.0], $stream->filter(Filters::isFloat())->toArray());
        
        self::assertSame(12, $sumInts->result());
        self::assertSame('abcdabcd', $strings->toString(''));
        
        //third iteration
        self::assertSame([2 => 15.0, 6 => false, 9 => 25.0], $stream->toArray(true));
        
        self::assertSame(18, $sumInts->result());
        self::assertSame('abcdabcdabcd', $strings->toString(''));
    }
    
    public function test_prototype_with_switch(): void
    {
        $sumInts = Reducers::sum();
        $strings = Collectors::values();
        
        $discriminator = static function ($v): string {
            switch (true) {
                case \is_string($v): return 'str';
                case \is_int($v): return 'int';
                default: return 'other';
            }
        };
        
        $stream = Stream::prototype()
            ->limit(10)
            ->switch($discriminator, [
                'str' => $strings,
                'int' => $sumInts,
            ])
            ->join(['a', 1, 15.0, 'b', 2, 'c', false, 3, 'd', 25.0, 'e']);
        
        //first iteration
        self::assertSame(3, $stream->count()->get());
        
        self::assertSame(6, $sumInts->result());
        self::assertSame('abcd', $strings->toString(''));
        
        //second iteration
        self::assertSame([15.0, 25.0], $stream->filter(Filters::isFloat())->toArray());
        
        self::assertSame(12, $sumInts->result());
        self::assertSame('abcdabcd', $strings->toString(''));
        
        //third iteration
        self::assertSame([2 => 15.0, 6 => false, 9 => 25.0], $stream->toArray(true));
        
        self::assertSame(18, $sumInts->result());
        self::assertSame('abcdabcdabcd', $strings->toString(''));
    }
    
    public function test_prototype_with_dispatch(): void
    {
        $sumInts = Reducers::sum();
        $strings = Collectors::values();
        
        $discriminator = static function ($v): string {
            switch (true) {
                case \is_string($v): return 'str';
                case \is_int($v): return 'int';
                default: return 'other';
            }
        };
        
        $stream = Stream::prototype(['a', 1, 15.0, 'b', 2, 'c', false, 3, 'd', 25.0, 'e'])
            ->dispatch($discriminator, [
                'str' => $strings,
                'int' => $sumInts,
                'other' => Consumers::idle(),
            ])
            ->limit(5);
        
        //first iteration
        self::assertSame(5, $stream->count()->get());
        
        self::assertSame(3, $sumInts->result());
        self::assertSame('ab', $strings->toString(''));
        
        //second iteration
        self::assertSame([15.0], $stream->filter('is_float')->toArray());
        
        self::assertSame(6, $sumInts->result());
        self::assertSame('abab', $strings->toString(''));
        
        //third iteration
        self::assertSame(['a', 1, 15.0, 'b', 2], $stream->toArray(true));
        
        self::assertSame(9, $sumInts->result());
        self::assertSame('ababab', $strings->toString(''));
    }
    
    public function test_prototype_with_multiple_cache_works_like_single_cache(): void
    {
        $stream = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4])
            ->onlyIntegers()
            ->countIn($counterBeforeCache)
            ->cache()
            ->cache()
            ->cache()
            ->countIn($counterAfterCache);
        
        self::assertSame(4, $stream->count()->get());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(4, $counterAfterCache);
        
        self::assertSame([1, 2, 3, 4], $stream->toArray());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(8, $counterAfterCache);
    }
    
    public function test_prototype_with_cache(): void
    {
        $this->performTest056(false);
    }
    
    public function test_prototype_with_cache_with_onerror_handler(): void
    {
        $this->performTest056(true);
    }
    
    private function performTest056(bool $onError): void
    {
        $stream = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4])
            ->onlyIntegers()
            ->countIn($counterBeforeCache)
            ->cache()
            ->countIn($counterAfterCache);
        
        if ($onError) {
            $stream = $stream->onError(OnError::abort());
        }
        
        self::assertSame(4, $stream->count()->get());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(4, $counterAfterCache);
        
        self::assertSame([1, 2, 3, 4], $stream->toArray());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(8, $counterAfterCache);
        
        self::assertSame([1, 3], $stream->without([2, 4])->toArray());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(12, $counterAfterCache);
        
        self::assertSame([2, 4], $stream->only([2, 4])->toArray());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(16, $counterAfterCache);
        
        $sequence = $stream->scan(0, Reducers::sum())->countIn($thirdCounter)->reindex()->cache();
        
        self::assertSame(5, $sequence->count()->get());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(20, $counterAfterCache);
        self::assertSame(5, $thirdCounter);
        
        self::assertSame([0, 1, 3, 6, 10], $sequence->toArray());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(20, $counterAfterCache);
        self::assertSame(5, $thirdCounter);
        
        self::assertSame('[1,3,6,10]', $sequence->skip(1)->toJson());
        self::assertSame(4, $counterBeforeCache);
        self::assertSame(20, $counterAfterCache);
        self::assertSame(5, $thirdCounter);
    }
    
    public function test_cache_can_handle_non_unique_keys(): void
    {
        $this->performTest057(false);
    }
    
    public function test_cache_can_handle_non_unique_keys_with_onerror_handler(): void
    {
        $this->performTest057(true);
    }
    
    private function performTest057(bool $onError): void
    {
        $counter = Consumers::counter();
        $stream = Stream::prototype([1, 2, 1, 3, 2, 1, 2])->flip()->callOnce($counter)->cache();
        
        if ($onError) {
            $stream = $stream->onError(OnError::abort());
        }
        
        self::assertSame(7, $stream->count()->get());
        self::assertSame(1, $counter->get());
        
        self::assertSame([[1, 0], [2, 1], [1, 2], [3, 3], [2, 4], [1, 5], [2, 6]], $stream->makeTuple()->toArray());
        self::assertSame(1, $counter->get());
        
        self::assertSame([1 => 5, 2 => 6, 3 => 3], $stream->toArrayAssoc());
        self::assertSame(1, $counter->get());
    }
    
    public function test_prototypes_wih_shared_cache(): void
    {
        $this->performTest058(false);
    }
    
    public function test_prototypes_wih_shared_cache_with_onerror_handler(): void
    {
        $this->performTest058(true);
    }
    
    private function performTest058(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4])->onlyIntegers()->countIn($counter)->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->greaterThan(2);
        $stream3 = $stream1->lessOrEqual(2);
        
        self::assertSame(7, $stream2->reduce(Reducers::sum())->get());
        self::assertSame(4, $counter);
        
        self::assertSame(2, $stream3->reduce(Reducers::product())->get());
        self::assertSame(4, $counter);
        
        self::assertSame([1, 2, 3, 4], $stream1->toArray());
        self::assertSame(4, $counter);
    }
    
    public function test_prototypes_wih_shared_cache_and_has(): void
    {
        $this->performTest059(false);
    }
    
    public function test_prototypes_wih_shared_cache_and_has_with_onerror_handler(): void
    {
        $this->performTest059(true);
    }
    
    private function performTest059(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4])->onlyIntegers()->countIn($counter)->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->greaterThan(2);
        $stream3 = $stream1->lessOrEqual(2);
        
        self::assertTrue($stream2->has(3)->get());
        self::assertSame(3, $counter);
        
        self::assertFalse($stream3->has(3)->get());
        self::assertSame(7, $counter);
        
        self::assertSame([1, 2, 3, 4], $stream1->toArray());
        self::assertSame(7, $counter);
        
        self::assertSame('[1,2]', $stream3->toJson());
    }
    
    public function test_prototypes_wih_shared_cache_and_limit(): void
    {
        $this->performTest060(false);
    }
    
    public function test_prototypes_wih_shared_cache_and_limit_with_onerror_handler(): void
    {
        $this->performTest060(true);
    }
    
    private function performTest060(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4, 'e', 5, 'f', 6])
            ->onlyIntegers()
            ->limit(4)
            ->countIn($counter)
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->greaterThan(2);
        $stream3 = $stream1->lessOrEqual(2);
        
        self::assertSame('3,4', $stream2->toString());
        self::assertSame(4, $counter);
        
        self::assertSame('1,2', $stream3->toString());
        self::assertSame(4, $counter);
        
        self::assertSame('1,2,3,4', $stream1->toString());
        self::assertSame(4, $counter);
    }
    
    public function test_prototypes_with_shared_cache_and_isEmpty(): void
    {
        $this->performTest061(false);
    }
    
    public function test_prototypes_with_shared_cache_and_isEmpty_with_onerror_handler(): void
    {
        $this->performTest061(true);
    }
    
    private function performTest061(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4])->onlyIntegers()->countIn($counter)->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::skip());
        }
        
        $stream2 = $stream1->greaterThan(2);
        $stream3 = $stream1->lessOrEqual(2);
        
        self::assertFalse($stream2->isEmpty()->get());
        self::assertSame(3, $counter);
        
        self::assertFalse($stream3->isEmpty()->get());
        self::assertSame(4, $counter);
        
        self::assertSame([1, 2, 3, 4], $stream1->toArray());
        self::assertSame(8, $counter);
        
        self::assertSame(3, $stream3->reduce(Reducers::sum())->get());
        self::assertSame(8, $counter);
        
        self::assertSame(7, $stream2->reduce(Reducers::sum())->get());
        self::assertSame(8, $counter);
    }
    
    public function test_prototypes_with_shared_cache_and_first(): void
    {
        $this->performTest062(false);
    }
    
    public function test_prototypes_with_shared_cache_and_first_with_onerror_handler(): void
    {
        $this->performTest062(true);
    }
    
    private function performTest062(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4])->onlyIntegers()->countIn($counter)->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->greaterThan(2);
        $stream3 = $stream1->lessOrEqual(2);
        
        self::assertSame(3, $stream2->first()->get());
        self::assertSame(3, $counter);
        
        self::assertSame(1, $stream3->first()->get());
        self::assertSame(4, $counter);
        
        self::assertSame([1, 2, 3, 4], $stream1->toArray());
        self::assertSame(8, $counter);
        
        self::assertSame(3, $stream3->reduce(Reducers::sum())->get());
        self::assertSame(8, $counter);
        
        self::assertSame(7, $stream2->reduce(Reducers::sum())->get());
        self::assertSame(8, $counter);
    }
    
    public function test_prototypes_with_shared_cache_and_find(): void
    {
        $this->performTest063(false);
    }
    
    public function test_prototypes_with_shared_cache_and_find_with_onerror_handler(): void
    {
        $this->performTest063(true);
    }
    
    private function performTest063(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3, 'd', 4])->onlyIntegers()->countIn($counter)->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->greaterThan(2);
        $stream3 = $stream1->lessOrEqual(2);
        
        self::assertTrue($stream1->find(2)->found());
        self::assertSame(2, $counter);
        
        self::assertSame(2, $stream2->count()->get());
        self::assertSame(6, $counter);
        
        self::assertSame('1,2', $stream3->toString());
        self::assertSame(6, $counter);
        
        self::assertSame(3, $stream2->first()->get());
        self::assertSame(6, $counter);
        
        self::assertSame([1, 2, 3, 4], $stream1->toArray());
        self::assertSame(6, $counter);
        
        self::assertSame(3, $stream3->reduce(Reducers::sum())->get());
        self::assertSame(6, $counter);
    }
    
    public function test_prototypes_with_shared_cache_and_sort(): void
    {
        $this->performTest064(false);
    }
    
    public function test_prototypes_with_shared_cache_and_sort_with_onerror_handler(): void
    {
        $this->performTest064(true);
    }
    
    private function performTest064(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 4, 'b', 2, 'c', 1, 'd', 3])
            ->onlyIntegers()
            ->countIn($counter)
            ->sort()
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->greaterThan(2);
        $stream3 = $stream1->lessOrEqual(2);
        
        self::assertTrue($stream1->find(2)->found());
        self::assertSame(4, $counter);
        
        self::assertSame(2, $stream2->count()->get());
        self::assertSame(4, $counter);
        
        self::assertSame('1,2', $stream3->toString());
        self::assertSame(4, $counter);
        
        self::assertSame(3, $stream2->first()->get());
        self::assertSame(4, $counter);
        
        self::assertSame([5 => 1, 3 => 2, 7 => 3, 1 => 4], $stream1->toArrayAssoc());
        self::assertSame(4, $counter);
        
        self::assertSame(3, $stream3->reduce(Reducers::sum())->get());
        self::assertSame(4, $counter);
    }
    
    public function test_prototypes_with_shared_cache_and_tail(): void
    {
        $this->performTest065(false);
    }
    
    public function test_prototypes_with_shared_cache_and_tail_with_onerror_handler(): void
    {
        $this->performTest065(true);
    }
    
    private function performTest065(bool $onError): void
    {
        $stream1 = Stream::prototype([6, 3, 'foo', 4, 7, 5, 'bar', 1, 2])
            ->onlyIntegers()
            ->countIn($counter)
            ->tail(3)
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->filter(Filters::number()->isOdd());
        
        self::assertTrue($stream1->has(1)->get());
        self::assertSame(7, $counter);
        
        self::assertSame([5, 1], $stream2->toArray());
        self::assertSame(7, $counter);
        
        $stream3 = $stream1->lessThan(5);
        
        self::assertSame('1,2', $stream3->toString());
        self::assertSame(7, $counter);
    }
    
    public function test_prototypes_with_shared_cache_and_categorize(): void
    {
        $this->performTest066(false);
    }
    
    public function test_prototypes_with_shared_cache_and_categorize_with_onerror_handler(): void
    {
        $this->performTest066(true);
    }
    
    private function performTest066(bool $onError): void
    {
        $stream = Stream::prototype([6, 3, 9, 2, 5, 1, 4, 2, 8, 4, 6, 5, 2, 9])
            ->call($counter = Consumers::counter())
            ->categorize(Discriminators::evenOdd())
            ->cache();
        
        if ($onError) {
            $stream = $stream->onError(OnError::abort());
        }
        
        $stream->run();
        
        self::assertSame(14, $counter->get());
        self::assertSame(2, $stream->count()->get());
        self::assertSame(14, $counter->get());
        
        self::assertSame('even,odd', $stream->collectKeys()->toString());
        self::assertSame(14, $counter->get());
        
        self::assertSame([
            0 => 6,
            3 => 2,
            6 => 4,
            7 => 2,
            8 => 8,
            9 => 4,
            10 => 6,
            12 => 2,
        ], $stream->sort(By::sizeDesc())->first()->get());
        
        self::assertSame(14, $counter->get());
    }
    
    public function test_prototypes_with_shared_cache_and_fork(): void
    {
        $stream1 = Stream::prototype([6, 3, 9, 2, 5, 1, 4, 2, 8, 4, 6, 5, 2, 9])
            ->callOnce($counter = Consumers::counter())
            ->filter(Filters::number()->between(0, 10))
            ->fork(Discriminators::evenOdd(), Stream::empty()->sort()->collectValues())
            ->cache();
        
        $stream2 = $stream1->filterKey('even')->map(Reducers::count())->first();
        $stream3 = $stream1->filterKey('odd')->flat()->cache();
        $stream4 = $stream1->limit(1);
        
        self::assertSame(0, $counter->get());
        
        self::assertSame([2, 2, 2, 4, 4, 6, 6, 8], $stream4->first()->toArray());
        self::assertSame(1, $counter->get());
        
        self::assertSame(6, $stream3->count()->get());
        self::assertSame(1, $counter->get());
        
        self::assertSame([1, 3, 5, 5, 9, 9], $stream3->toArray());
        self::assertSame(1, $counter->get());
        
        self::assertSame(8, $stream2->get());
        self::assertSame(1, $counter->get());
    }
    
    public function test_prototypes_with_shared_cache_and_consume_1(): void
    {
        $this->performTest067(false, null);
    }
    
    public function test_prototypes_with_shared_cache_and_consume_1_with_onerror_handler(): void
    {
        $this->performTest067(true, null);
    }
    
    public function test_prototypes_with_shared_cache_and_consume_2(): void
    {
        $this->performTest067(false, true);
    }
    
    public function test_prototypes_with_shared_cache_and_consume_2_with_onerror_handler(): void
    {
        $this->performTest067(true, true);
    }
    
    private function performTest067(bool $onError, ?bool $reindex): void
    {
        $stream1 = Stream::prototype()
            ->countIn($counter)
            ->categorize(Discriminators::evenOdd(), $reindex)
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->find('even', Check::KEY);
        $stream3 = $stream1->find('odd', Check::KEY);
        $stream4 = $stream1->sort(By::size())->reverse()->first();
        
        $stream4->consume([6, 3, 9, 2]);
        self::assertSame(4, $counter);
        
        $stream3->consume([5, 1, 4, 2]);
        self::assertSame(8, $counter);
        
        $stream2->consume([8, 4, 6, 5]);
        self::assertSame(12, $counter);
        
        $stream1->consume([2, 9]);
        self::assertSame(14, $counter);
        
        self::assertSame([8, 4, 6], $stream2->get());
        self::assertSame(14, $counter);
        
        self::assertSame([5, 1], $stream3->get());
        self::assertSame(14, $counter);
        
        self::assertSame('[6,2]', $stream4->toJson());
        self::assertSame(14, $counter);
        
        self::assertSame(['even', 'odd'], $stream1->collectKeys()->toArray());
        self::assertSame(14, $counter);
        
        self::assertSame([1, 3], $stream4->wrap([1, 2, 3])->toArray());
        self::assertSame(17, $counter);
        
        self::assertSame([8, 4, 6], $stream2->get());
        self::assertSame(17, $counter);
        
        self::assertSame([5, 1], $stream3->get());
        self::assertSame(17, $counter);
        
        self::assertSame('[6,2]', $stream4->toJson());
        self::assertSame(17, $counter);
        
        self::assertSame('even,odd', $stream1->collectKeys()->toString());
        self::assertSame(17, $counter);
    }
    
    public function test_prototype_with_cache_and_join(): void
    {
        $this->performTest068(false);
    }
    
    public function test_prototype_with_cache_and_join_with_onerror_handler(): void
    {
        $this->performTest068(true);
    }
    
    private function performTest068(bool $onError): void
    {
        $stream = Stream::prototype()
            ->join([1, 'a', 2, 3, 'b', 4, 5, 'c'])
            ->onlyStrings()
            ->countIn($counter)
            ->cache();
        
        if ($onError) {
            $stream = $stream->onError(OnError::abort());
        }
        
        self::assertSame('abc', $stream->toString(''));
        self::assertSame(3, $counter);
        
        self::assertSame(3, $stream->count()->get());
        self::assertSame(3, $counter);
        
        $other = $stream->join(['e', 6, 'f', 7]);
        
        self::assertSame('abcef', $other->toString(''));
        self::assertSame(8, $counter);
        
        self::assertSame('fecba', $other->reverse()->toString(''));
        self::assertSame(8, $counter);
        
        self::assertSame('abc', $stream->toString(''));
        self::assertSame(8, $counter);
    }
    
    public function test_prototype_with_shared_cache_and_join(): void
    {
        $this->performTest069(false);
    }
    
    public function test_prototype_with_shared_cache_and_join_with_onerror_handler(): void
    {
        $this->performTest069(true);
    }
    
    private function performTest069(bool $onError): void
    {
        $stream1 = Stream::prototype()
            ->join([1, 'a', 2, 3, 'b', 4, 5, 'c'])
            ->onlyStrings()
            ->countIn($counter)
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->map('strtoupper')->reverse();
        
        self::assertSame('abc', $stream1->toString(''));
        self::assertSame(3, $counter);
        
        self::assertSame(3, $stream1->count()->get());
        self::assertSame(3, $counter);
        
        self::assertSame('CBA', $stream2->toString(''));
        self::assertSame(3, $counter);
        
        //when Producer has been changed, cache should be detached and reset;
        //cache in the new stream should be independent of other caches
        $stream3 = $stream2->join(['t', 1, 'u', 2, 'v']);
        
        self::assertSame('VUTCBA', $stream3->toString(''));
        self::assertSame(9, $counter);
        
        self::assertSame('abc', $stream2->reverse()->map('strtolower')->toString(''));
        self::assertSame(9, $counter);
        
        self::assertSame(['b', 'c', 't', 'u'], $stream3->map('strtolower')->reverse()->skip(1)->limit(4)->toArray());
        self::assertSame(9, $counter);
        
        //detach of cache again
        self::assertSame('a,b,c,e,f,g', $stream1->join([6, 'e', 7, 'f', 8, 'g'])->toString());
        self::assertSame(15, $counter);
        
        self::assertSame(3, $stream1->count()->get());
        self::assertSame(15, $counter);
        
        self::assertSame(6, $stream3->count()->get());
        self::assertSame(15, $counter);
    }
    
    public function test_prototype_with_cache_and_wrap(): void
    {
        $this->performTest070(false);
    }
    
    public function test_prototype_with_cache_and_wrap_with_onerror_handler(): void
    {
        $this->performTest070(true);
    }
    
    private function performTest070(bool $onError): void
    {
        $stream1 = Stream::prototype([1, 'a', 2, 3, 'b', 4, 5, 'c'])->onlyStrings()->countIn($counter)->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream2 = $stream1->map('strtoupper')->reverse();
        
        self::assertSame('abc', $stream1->toString(''));
        self::assertSame(3, $counter);
        
        self::assertSame(3, $stream1->count()->get());
        self::assertSame(3, $counter);
        
        self::assertSame('CBA', $stream2->toString(''));
        self::assertSame(3, $counter);
        
        $stream3 = $stream2->wrap(['t', 1, 'u', 2]);
        
        self::assertSame('UT', $stream3->toString(''));
        self::assertSame(5, $counter);
        
        self::assertSame('abc', $stream2->reverse()->map('strtolower')->toString(''));
        self::assertSame(5, $counter);
        
        self::assertSame('u', $stream3->map('strtolower')->reverse()->skip(1)->toString(''));
        self::assertSame(5, $counter);
        
        //detach of cache again
        self::assertSame('e,f,g,h', $stream1->wrap([6, 'e', 7, 'f', 8, 'g', 9, 'h'])->toString());
        self::assertSame(9, $counter);
        
        self::assertSame(3, $stream1->count()->get());
        self::assertSame(9, $counter);
        
        self::assertSame(2, $stream3->count()->get());
        self::assertSame(9, $counter);
    }
    
    public function test_prototype_transformation(): void
    {
        $this->performTest071(false);
    }
    
    public function test_prototype_transformation_with_onerror_handler(): void
    {
        $this->performTest071(true);
    }
    
    private function performTest071(bool $onError): void
    {
        $counter = Consumers::counter();
        $stream1 = Stream::prototype(['a', 'b', 1, 'c', 'd', 2, 'e'])->callOnce($counter)->onlyStrings();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream7 = $stream1->count(); //new stream
        self::assertNotSame($stream1, $stream7);
        
        self::assertSame(5, $stream7->get()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->reduce(Reducers::concat()); //new streamm
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame('abcde', $stream2->get()); //iteration
        self::assertSame(2, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream2->wrap(['f', 3, 'g', 4, 'h']); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame('fgh', $stream3->get()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(3, $counter->get());
        
        $stream4 = $stream1->join([5, 'f', 6, 'g'])->reverse(); //new stream
        self::assertNotSame($stream1, $stream4);
        
        self::assertSame('gfedcba', $stream4->toString('')); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame('fgh', $stream3->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(4, $counter->get());
        
        $stream5 = $stream2->transform('strtoupper'); //new stream
        self::assertNotSame($stream2, $stream5);
        
        self::assertSame('ABCDE', $stream5->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame('gfedcba', $stream4->toString('')); //iteration
        self::assertSame(5, $counter->get());
        
        self::assertSame('fgh', $stream3->get());
        self::assertSame(5, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(5, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(5, $counter->get());
        
        $stream6 = $stream5->wrap(['o', 'p', 'q']); //new stream
        self::assertNotSame($stream5, $stream6);
        
        self::assertSame('OPQ', $stream6->get()); //iteration
        self::assertSame(6, $counter->get());
        
        self::assertSame('ABCDE', $stream5->get());
        self::assertSame(6, $counter->get());
        
        self::assertSame('gfedcba', $stream4->toString('')); //iteration
        self::assertSame(7, $counter->get());
        
        self::assertSame('fgh', $stream3->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(7, $counter->get());
    }
    
    public function test_prototype_transformation_cache(): void
    {
        $this->performTest072(false);
    }
    
    public function test_prototype_transformation_cache_with_onerror_handler(): void
    {
        $this->performTest072(true);
    }
    
    private function performTest072(bool $onError): void
    {
        $counter = Consumers::counter();
        $stream1 = Stream::prototype(['a', 'b', 1, 'c', 'd', 2, 'e'])->callOnce($counter)->onlyStrings()->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream7 = $stream1->count(); //new stream
        self::assertNotSame($stream1, $stream7);
        
        self::assertSame(5, $stream7->get()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->reduce(Reducers::concat()); //new streamm
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(1, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream2->wrap(['f', 3, 'g', 4, 'h']); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame('fgh', $stream3->get()); //iteration
        self::assertSame(2, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream1->join([5, 'f', 6, 'g'])->reverse(); //new stream
        self::assertNotSame($stream1, $stream4);
        
        self::assertSame('gfedcba', $stream4->toString('')); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame('fgh', $stream3->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(3, $counter->get());
        
        $stream5 = $stream2->transform('strtoupper'); //new stream
        self::assertNotSame($stream2, $stream5);
        
        self::assertSame('ABCDE', $stream5->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame('gfedcba', $stream4->toString(''));
        self::assertSame(3, $counter->get());
        
        self::assertSame('fgh', $stream3->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(3, $counter->get());
        
        $stream6 = $stream5->wrap(['o', 'p', 'q']); //new stream
        self::assertNotSame($stream5, $stream6);
        
        self::assertSame('OPQ', $stream6->get()); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame('ABCDE', $stream5->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame('gfedcba', $stream4->toString(''));
        self::assertSame(4, $counter->get());
        
        self::assertSame('fgh', $stream3->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame('abcde', $stream2->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame(5, $stream7->get());
        self::assertSame(4, $counter->get());
    }
    
    public function test_prototype_categorize(): void
    {
        $this->performTest073(false);
    }
    
    public function test_prototype_categorize_with_onerror_handler(): void
    {
        $this->performTest073(true);
    }
    
    private function performTest073(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3])->categorize('is_string', true);
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame(2, $stream1->count()->get());
        
        $stream2 = $stream1->filterKey(1);
        self::assertSame('abc', $stream2->concat('')->toString());
        
        $stream3 = $stream1->wrap(['d', 'e']);
        self::assertSame('de', $stream3->filter(1, Check::KEY)->concat('')->toString());
        
        $stream4 = $stream2->join(['f', 4, 'g'])->first()->transform(Reducers::concat());
        self::assertSame('abcfg', $stream4->get());
        
        self::assertSame(6, $stream1->filterKey(0)->flat()->reduce(Reducers::sum())->get());
    }
    
    public function test_prototype_categorize_cache(): void
    {
        $this->performTest074(false);
    }
    
    public function test_prototype_categorize_cache_with_onerror_handler(): void
    {
        $this->performTest074(true);
    }
    
    private function performTest074(bool $onError): void
    {
        $stream1 = Stream::prototype(['a', 1, 'b', 2, 'c', 3])
            ->countIn($counter)
            ->categorize('is_string', true)
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame(2, $stream1->count()->get());
        self::assertSame(6, $counter);
        
        $stream2 = $stream1->filterKey(1);
        self::assertSame('abc', $stream2->concat('')->toString());
        self::assertSame(6, $counter);
        
        $stream3 = $stream1->wrap(['d', 'e']);
        self::assertSame('de', $stream3->filter(1, Check::KEY)->concat('')->toString());
        self::assertSame(6+2, $counter);
        
        $stream4 = $stream2->join(['f', 4, 'g'])->first()->transform(Reducers::concat());
        self::assertSame('abcfg', $stream4->get());
        self::assertSame(6+2+6+3, $counter);
        
        self::assertSame(6, $stream1->filterKey(0)->flat()->reduce(Reducers::sum())->get());
        self::assertSame(6+2+6+3, $counter);
    }
    
    public function test_prototype_fork(): void
    {
        $this->performTest075(false);
    }
    
    public function test_prototype_fork_with_onerror_handler(): void
    {
        $this->performTest075(true);
    }
    
    private function performTest075(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype()
            ->callOnce($counter)
            ->onlyIntegers()
            ->fork(Discriminators::evenOdd(), Stream::empty()->greaterOrEqual(5)->lessOrEqual(10)->collectValues())
            ->join([5, 2, 3, 'a', 4, 1, 6, 'b', 15, 2, 9, 'c', 3, 6, 4, 'd', 12]);
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame(['odd' => [5, 9], 'even' => [6, 6]], $stream1->toArrayAssoc()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame(2, $stream1->count()->get()); //iteration
        self::assertSame(2, $counter->get());
        
        self::assertSame('{"odd":[5,9],"even":[6,6]}', $stream1->toJsonAssoc()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame(['odd' => 14, 'even' => 12], $stream1->map(Reducers::sum())->toArrayAssoc()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream2 = $stream1->wrap(['c', 8, 3, 7, 'a', 5, 6, 9, 'd', 11]);
        
        self::assertSame(['even' => [8, 6], 'odd' => [7, 5, 9]], $stream2->toArrayAssoc()); //iteration
        self::assertSame(5, $counter->get());
        
        self::assertSame(['odd' => 9, 'even' => 6], $stream1->map(Reducers::max())->toArrayAssoc()); //iteration
        self::assertSame(6, $counter->get());
        
        $stream3 = $stream2->join([10, 'h', 5, 2, 's', 6]);
        
        self::assertSame(['even' => '8,6,10,6', 'odd' => '7,5,9,5'], $stream3->concat(',')->toArrayAssoc()); //iteration
        self::assertSame(7, $counter->get());
        
        self::assertSame(['odd', 'even'], $stream1->collectKeys()->toArray()); //iteration
        self::assertSame(8, $counter->get());
    }
    
    public function test_prototype_fork_cache(): void
    {
        $this->performTest076(false);
    }
    
    public function test_prototype_fork_cache_with_onerror_handler(): void
    {
        $this->performTest076(true);
    }
    
    private function performTest076(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype()
            ->callOnce($counter)
            ->onlyIntegers()
            ->fork(Discriminators::evenOdd(), Stream::empty()->greaterOrEqual(5)->lessOrEqual(10)->collectValues())
            ->join([5, 2, 3, 'a', 4, 1, 6, 'b', 15, 2, 9, 'c', 3, 6, 4, 'd', 12])
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame(['odd' => [5, 9], 'even' => [6, 6]], $stream1->toArrayAssoc()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame(2, $stream1->count()->get());
        self::assertSame(1, $counter->get());
        
        self::assertSame('{"odd":[5,9],"even":[6,6]}', $stream1->toJsonAssoc());
        self::assertSame(1, $counter->get());
        
        self::assertSame(['odd' => 14, 'even' => 12], $stream1->map(Reducers::sum())->toArrayAssoc());
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->wrap(['c', 8, 3, 7, 'a', 5, 6, 9, 'd', 11]);
        
        self::assertSame(['even' => [8, 6], 'odd' => [7, 5, 9]], $stream2->toArrayAssoc()); //iteration
        self::assertSame(2, $counter->get());
        
        self::assertSame(['odd' => 9, 'even' => 6], $stream1->map(Reducers::max())->toArrayAssoc());
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream2->join([10, 'h', 5, 2, 's', 6]);
        
        self::assertSame(['even' => '8,6,10,6', 'odd' => '7,5,9,5'], $stream3->concat(',')->toArrayAssoc()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame(['odd', 'even'], $stream1->collectKeys()->toArray());
        self::assertSame(3, $counter->get());
    }
    
    public function test_prototype_forkMatch(): void
    {
        $this->performTest077(false);
    }
    
    public function test_prototype_forkMatch_with_onerror_handler(): void
    {
        $this->performTest077(true);
    }
    
    private function performTest077(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([4, 'a', 3, 15.6, 'b', 'c', 9.24, 7, 'd', 6, 2.5, 'e', 5])
            ->callOnce($counter)
            ->forkMatch($this->forkMatchDiscriminator(), $this->forkMatchHandlers());
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame($this->expectedForkMatchStream1Result(), $stream1->toArrayAssoc()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame(4, $stream1->count()->get()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream2 = $stream1->join([9, 2, 'f', 3]);
        
        self::assertSame($this->expectedForkMatchStream2Result(), $stream2->toArrayAssoc()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream3 = $stream1->wrap([5, 'n', 6.5, 'd', 4, 2, 'i', 9]);
        
        self::assertSame($this->expectedForkMatchStream3Result(), $stream3->toArrayAssoc()); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame($this->expectedForkMatchStream2Result(), $stream2->toArrayAssoc()); //iteration
        self::assertSame(5, $counter->get());
        
        self::assertSame($this->expectedForkMatchStream1Result(), $stream1->toArrayAssoc()); //iteration
        self::assertSame(6, $counter->get());
    }
    
    public function test_prototype_forkMatch_cache(): void
    {
        $this->performTest078(false);
    }
    
    public function test_prototype_forkMatch_cache_with_onerror_handler(): void
    {
        $this->performTest078(true);
    }
    
    private function performTest078(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([4, 'a', 3, 15.6, 'b', 'c', 9.24, 7, 'd', 6, 2.5, 'e', 5])
            ->callOnce($counter)
            ->forkMatch($this->forkMatchDiscriminator(), $this->forkMatchHandlers())
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame($this->expectedForkMatchStream1Result(), $stream1->toArrayAssoc()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame(4, $stream1->count()->get());
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->join([9, 2, 'f', 3]);
        
        self::assertSame($this->expectedForkMatchStream2Result(), $stream2->toArrayAssoc()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream1->wrap([5, 'n', 6.5, 'd', 4, 2, 'i', 9]);
        
        self::assertSame($this->expectedForkMatchStream3Result(), $stream3->toArrayAssoc()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame($this->expectedForkMatchStream2Result(), $stream2->toArrayAssoc());
        self::assertSame(3, $counter->get());
        
        self::assertSame($this->expectedForkMatchStream1Result(), $stream1->toArrayAssoc());
        self::assertSame(3, $counter->get());
    }
    
    private function forkMatchDiscriminator(): callable
    {
        return static function ($value): string {
            if (\is_string($value)) {
                return 'string';
            } elseif (\is_float($value)) {
                return 'float';
            } elseif (\is_int($value)) {
                return ($value & 1) === 0 ? 'even' : 'odd';
            } elseif (\is_bool($value)) {
                return 'bool';
            } else {
                return 'uknown';
            }
        };
    }
    
    private function forkMatchHandlers(): array
    {
        return [
            'even' => Reducers::min(),
            'odd' => Reducers::max(),
            'string' => Stream::empty()->map('strtoupper')->reduce(Reducers::concat('|')),
            'float' => Collectors::values(),
        ];
    }
    
    private function expectedForkMatchStream1Result(): array
    {
        return [
            'even' => 4,
            'odd' => 7,
            'string' => 'A|B|C|D|E',
            'float' => [15.6, 9.24, 2.5],
        ];
    }
    
    private function expectedForkMatchStream2Result(): array
    {
        return [
            'even' => 2,
            'odd' => 9,
            'string' => 'A|B|C|D|E|F',
            'float' => [15.6, 9.24, 2.5],
        ];
    }
    
    private function expectedForkMatchStream3Result(): array
    {
        return [
            'even' => 2,
            'odd' => 9,
            'string' => 'N|D|I',
            'float' => [6.5],
        ];
    }
    
    public function test_prototype_forkMatch_2(): void
    {
        $this->performTest079(false);
    }
    
    public function test_prototype_forkMatch_2_with_onerror_handler(): void
    {
        $this->performTest079(true);
    }
    
    private function performTest079(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream = Stream::prototype()
            ->callOnce($counter)
            ->forkMatch(Discriminators::yesNo('is_string', 'str', 'int'), [
                'str' => Stream::empty()->map('strtoupper')->reduce(Reducers::concat()),
                'int' => Stream::empty()
                    ->filter(Filters::number()->between(5, 10))
                    ->classify(Discriminators::evenOdd())
                    ->forkByKey(Reducers::sum())
                    ->collect()
            ])
            ->flat(1)
            ->join([5, 2, 3, 'a', 4, 1, 6, 'b', 15, 2, 9, 'c', 3, 6, 4, 'd', 12]);
        
        if ($onError) {
            $stream = $stream->onError(OnError::abort());
        }
        
        //first iteration
        self::assertSame(['str' => 'ABCD', 'odd' => 14, 'even' => 12], $stream->toArrayAssoc());
        self::assertSame(1, $counter->get());
        
        //second iteration
        self::assertSame(3, $stream->count()->get());
        self::assertSame(2, $counter->get());
        
        //third iteration
        self::assertSame('{"str":"ABCD","odd":14,"even":12}', $stream->toJsonAssoc());
        self::assertSame(3, $counter->get());
        
        //fourth iteration
        self::assertSame(['even', 'odd', 'str'], $stream->collectKeys()->transform('sort')->get());
        self::assertSame(4, $counter->get());
    }
    
    public function test_prototype_gather(): void
    {
        $this->performTest080(false);
    }
    
    public function test_prototype_gather_with_onerror_handler(): void
    {
        $this->performTest080(true);
    }
    
    private function performTest080(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([7, 3, 'foo', 8, 2, 'bar', 5])
            ->callOnce($counter)
            ->onlyIntegers()
            ->gather(true)
            ->mapKey('numbers');
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame('{"numbers":[7,3,8,2,5]}', $stream1->toJsonAssoc()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->map(Reducers::sum());
        
        self::assertSame(['numbers' => 25], $stream2->toArrayAssoc()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream2->join([5, 'zoo', 4, 6]);
        
        self::assertSame(['numbers' => 40], $stream3->toArrayAssoc()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream4 = $stream1->wrap([1, 2, 3]);
        
        self::assertSame('{"numbers":[1,2,3]}', $stream4->toJsonAssoc()); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame(['numbers' => 40], $stream3->toArrayAssoc()); //iteration
        self::assertSame(5, $counter->get());
        
        self::assertSame(['numbers' => 25], $stream2->toArrayAssoc()); //iteration
        self::assertSame(6, $counter->get());
        
        self::assertSame('{"numbers":[7,3,8,2,5]}', $stream1->toJsonAssoc()); //iteration
        self::assertSame(7, $counter->get());
    }
    
    public function test_prototype_gather_cache(): void
    {
        $this->performTest081(false);
    }
    
    public function test_prototype_gather_cache_with_onerror_handler(): void
    {
        $this->performTest081(true);
    }
    
    private function performTest081(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([7, 3, 'foo', 8, 2, 'bar', 5])
            ->callOnce($counter)
            ->onlyIntegers()
            ->gather(true)
            ->mapKey('numbers')
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame('{"numbers":[7,3,8,2,5]}', $stream1->toJsonAssoc()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->map(Reducers::sum());
        
        self::assertSame(['numbers' => 25], $stream2->toArrayAssoc());
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream2->join([5, 'zoo', 4, 6]);
        
        self::assertSame(['numbers' => 40], $stream3->toArrayAssoc()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream1->wrap([1, 2, 3]);
        
        self::assertSame('{"numbers":[1,2,3]}', $stream4->toJsonAssoc()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame(['numbers' => 40], $stream3->toArrayAssoc());
        self::assertSame(3, $counter->get());
        
        self::assertSame(['numbers' => 25], $stream2->toArrayAssoc());
        self::assertSame(3, $counter->get());
        
        self::assertSame('{"numbers":[7,3,8,2,5]}', $stream1->toJsonAssoc());
        self::assertSame(3, $counter->get());
    }
    
    public function test_prototype_reverse(): void
    {
        $this->performTest082(false);
    }
    
    public function test_prototype_reverse_with_onerror_handler(): void
    {
        $this->performTest082(true);
    }
    
    private function performTest082(bool $onError): void
    {
        $counter = Consumers::counter();
        $stream1 = Stream::prototype(['a', 'b', 'ef', 'c', 'd'])->callOnce($counter)->reverse();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame('dcefba', $stream1->toString('')); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->filter(Filters::length()->eq(1));
        
        self::assertSame('dcba', $stream2->toString('')); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream2->wrap(['x', 'yu', 'y', 'we', 'z'])->reverse();
        
        self::assertSame('xyz', $stream3->toString('')); //iteration
        self::assertSame(3, $counter->get());
        
        $stream4 = $stream1->join(['e', 'ghi', 's', 'jk', 'z'])->filter(Filters::length()->gt(1));
        
        self::assertSame('jk-ghi-ef', $stream4->toString('-')); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame('xyz', $stream3->toString('')); //iteration
        self::assertSame(5, $counter->get());
        
        self::assertSame('dcba', $stream2->toString('')); //iteration
        self::assertSame(6, $counter->get());
        
        self::assertSame('dcefba', $stream1->toString('')); //iteration
        self::assertSame(7, $counter->get());
    }
    
    public function test_prototype_reverse_cache(): void
    {
        $this->performTest083(false);
    }
    
    public function test_prototype_reverse_cache_with_onerror_handler(): void
    {
        $this->performTest083(true);
    }
    
    private function performTest083(bool $onError): void
    {
        $counter = Consumers::counter();
        $stream1 = Stream::prototype(['a', 'b', 'ef', 'c', 'd'])->callOnce($counter)->reverse()->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame('dcefba', $stream1->toString('')); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->filter(Filters::length()->eq(1));
        
        self::assertSame('dcba', $stream2->toString(''));
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream2->wrap(['x', 'yu', 'y', 'we', 'z'])->reverse();
        
        self::assertSame('xyz', $stream3->toString('')); //iteration
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream1->join(['e', 'ghi', 's', 'jk', 'z'])->filter(Filters::length()->gt(1));
        
        self::assertSame('jk-ghi-ef', $stream4->toString('-')); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame('xyz', $stream3->toString(''));
        self::assertSame(3, $counter->get());
        
        self::assertSame('dcba', $stream2->toString(''));
        self::assertSame(3, $counter->get());
        
        self::assertSame('dcefba', $stream1->toString(''));
        self::assertSame(3, $counter->get());
    }
    
    public function test_prototype_segregate(): void
    {
        $this->performTest084(false);
    }
    
    public function test_prototype_segregate_with_onerror_handler(): void
    {
        $this->performTest084(true);
    }
    
    private function performTest084(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([4, 2, 3, 7, 5, 1, 4, 3, 6, 5, 8, 9, 0, 4, 8, 0, 3, 4, 6, 8, 2, 1])
            ->callOnce($counter)
            ->greaterThan(0)
            ->lessOrEqual(5)
            ->segregate(null, true);
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame([[1, 1], [2, 2], [3, 3, 3], [4, 4, 4, 4], [5, 5]], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->count();
        
        self::assertSame(5, $stream2->get()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream1->map(Reducers::count());
        
        self::assertSame([2, 2, 3, 4, 2], $stream3->toArray()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream4 = $stream3->join([3, 7, 5, 6, 2]);
        
        self::assertSame([2, 3, 4, 4, 3], $stream4->toArray()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream5 = $stream1->wrap([8, 2, 3, 5, 7, 1, 4, 2, 3]);
        
        self::assertSame([[1], [2, 2], [3, 3], [4], [5]], $stream5->toArray()); //iteration
        self::assertSame(5, $counter->get());
        
        self::assertSame([2, 3, 4, 4, 3], $stream4->toArray()); //iteration
        self::assertSame(6, $counter->get());
        
        self::assertSame([2, 2, 3, 4, 2], $stream3->toArray()); //iteration
        self::assertSame(7, $counter->get());
        
        self::assertSame(5, $stream2->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame([[1, 1], [2, 2], [3, 3, 3], [4, 4, 4, 4], [5, 5]], $stream1->toArray()); //iteration
        self::assertSame(8, $counter->get());
    }
    
    public function test_prototype_segregate_cache(): void
    {
        $this->performTest085(false);
    }
    
    public function test_prototype_segregate_cache_with_onerror_handler(): void
    {
        $this->performTest085(true);
    }
    
    private function performTest085(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([4, 2, 3, 7, 5, 1, 4, 3, 6, 5, 8, 9, 0, 4, 8, 0, 3, 4, 6, 8, 2, 1])
            ->callOnce($counter)
            ->greaterThan(0)
            ->lessOrEqual(5)
            ->segregate(null, true)
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame([[1, 1], [2, 2], [3, 3, 3], [4, 4, 4, 4], [5, 5]], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->count();
        
        self::assertSame(5, $stream2->get());
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream1->map(Reducers::count());
        
        self::assertSame([2, 2, 3, 4, 2], $stream3->toArray());
        self::assertSame(1, $counter->get());
        
        $stream4 = $stream3->join([3, 7, 5, 6, 2]);
        
        self::assertSame([2, 3, 4, 4, 3], $stream4->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream5 = $stream1->wrap([8, 2, 3, 5, 7, 1, 4, 2, 3]);
        
        self::assertSame([[1], [2, 2], [3, 3], [4], [5]], $stream5->toArray()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame([2, 3, 4, 4, 3], $stream4->toArray()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame([2, 2, 3, 4, 2], $stream3->toArray()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame(5, $stream2->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame([[1, 1], [2, 2], [3, 3, 3], [4, 4, 4, 4], [5, 5]], $stream1->toArray()); //iteration
        self::assertSame(3, $counter->get());
    }
}