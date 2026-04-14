<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Consumer\Counter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrototypeBTest extends TestCase
{
    public function test_prototype_callWhile(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->callWhile('is_string', $counter)
            ->onlyIntegers();
        
        $this->performTest086($stream1, $counter);
    }
    
    public function test_prototype_callWhile_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->onError(OnError::abort())
            ->callWhile('is_string', $counter)
            ->onlyIntegers();
        
        $this->performTest086($stream1, $counter);
    }
    
    public function test_prototype_callUntil(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->callUntil('is_int', $counter)
            ->onlyIntegers();
        
        $this->performTest086($stream1, $counter);
    }
    
    public function test_prototype_callUntil_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->onError(OnError::abort())
            ->callUntil('is_int', $counter)
            ->onlyIntegers();
        
        $this->performTest086($stream1, $counter);
    }
    
    private function performTest086(Stream $stream1, Counter $counter): void
    {
        self::assertSame(3, $stream1->reduce(Reducers::min())->get()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream2 = $stream1->reduce(Reducers::sum()); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame(16, $stream2->get()); //iteration
        self::assertSame(8, $counter->get());
        
        $stream3 = $stream2->transform(static fn(int $sum): float => $sum / 2.0); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame(8.0, $stream3->get());
        self::assertSame(8, $counter->get());
        
        $stream4 = $stream3->wrap(['a', 'b', 7, 'e', 2, 5, 'u', 4]); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame(9.0, $stream4->get()); //iteration
        self::assertSame(10, $counter->get());
        
        self::assertSame(8.0, $stream3->get());
        self::assertSame(10, $counter->get());
        
        self::assertSame(16, $stream2->get());
        self::assertSame(10, $counter->get());
        
        self::assertSame('835', $stream1->toString('')); //iteration
        self::assertSame(14, $counter->get());
    }
    
    public function test_prototype_callWhile_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->callWhile('is_string', $counter)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest087($stream1, $counter);
    }
    
    public function test_prototype_callWhile_cache_with_onerrror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->onError(OnError::abort())
            ->callWhile('is_string', $counter)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest087($stream1, $counter);
    }
    
    public function test_prototype_callUntil_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->callUntil('is_int', $counter)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest087($stream1, $counter);
    }
    
    public function test_prototype_callUntil_cache_with_onerrror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 'c', 'd', 8, 'e', 3, 'f', 5])
            ->onError(OnError::abort())
            ->callUntil('is_int', $counter)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest087($stream1, $counter);
    }
    
    private function performTest087(Stream $stream1, Counter $counter): void
    {
        self::assertSame(3, $stream1->reduce(Reducers::min())->get()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream2 = $stream1->reduce(Reducers::sum()); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame(16, $stream2->get());
        self::assertSame(4, $counter->get());
        
        $stream3 = $stream2->transform(static fn(int $sum): float => $sum / 2.0); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame(8.0, $stream3->get());
        self::assertSame(4, $counter->get());
        
        $stream4 = $stream3->wrap(['a', 'b', 7, 'e', 2, 5, 'u', 4]); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame(9.0, $stream4->get()); //iteration
        self::assertSame(6, $counter->get());
        
        self::assertSame(8.0, $stream3->get());
        self::assertSame(6, $counter->get());
        
        self::assertSame(16, $stream2->get());
        self::assertSame(6, $counter->get());
        
        self::assertSame('835', $stream1->toString(''));
        self::assertSame(6, $counter->get());
    }
    
    public function test_prototype_mapWhile(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->callMax(1, $counter)
            ->mapWhile('is_int', static fn(int $v): int => $v * 2)
            ->onlyIntegers();
        
        $this->performTest088($stream1, $counter);
    }
    
    public function test_prototype_mapWhile_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->onError(OnError::abort())
            ->callMax(1, $counter)
            ->mapWhile('is_int', static fn(int $v): int => $v * 2)
            ->onlyIntegers();
        
        $this->performTest088($stream1, $counter);
    }
    
    public function test_prototype_mapUntil(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->callMax(1, $counter)
            ->mapUntil('is_string', static fn(int $v): int => $v * 2)
            ->onlyIntegers();
        
        $this->performTest088($stream1, $counter);
    }
    
    public function test_prototype_mapUntil_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->onError(OnError::abort())
            ->callMax(1, $counter)
            ->mapUntil('is_string', static fn(int $v): int => $v * 2)
            ->onlyIntegers();
        
        $this->performTest088($stream1, $counter);
    }
    
    private function performTest088(Stream $stream1, Counter $counter): void
    {
        $expectedNumbers = [6, 10, 4, 4, 1, 8, 6, 2, 5];
        
        self::assertSame($expectedNumbers, $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->reduce(Reducers::sum()); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame(\array_sum($expectedNumbers), $stream2->get()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream2->transform(static fn(int $sum): float => $sum / 2.0); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame(\array_sum($expectedNumbers) / 2.0, $stream3->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame(\array_sum($expectedNumbers), $stream2->get());
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream3->wrap([1, 2, 3, 'foo', 4, 5]); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame(\array_sum([2, 4, 6, 4, 5]) / 2.0, $stream4->get()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame(\array_sum($expectedNumbers) / 2.0, $stream3->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame(\array_sum($expectedNumbers), $stream2->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame(\count($expectedNumbers), $stream1->count()->get()); //iteration
        self::assertSame(4, $counter->get());
    }
    
    public function test_prototype_mapWhile_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->callMax(1, $counter)
            ->mapWhile('is_int', static fn(int $v): int => $v * 2)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest089($stream1, $counter);
    }
    
    public function test_prototype_mapWhile_cache_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->onError(OnError::abort())
            ->callMax(1, $counter)
            ->mapWhile('is_int', static fn(int $v): int => $v * 2)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest089($stream1, $counter);
    }
    
    public function test_prototype_mapUntil_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->callMax(1, $counter)
            ->mapUntil('is_string', static fn(int $v): int => $v * 2)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest089($stream1, $counter);
    }
    
    public function test_prototype_mapUntil_cache_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 2, 'foo', 4, 1, 8, 'bar', 6, 2, 5])
            ->onError(OnError::abort())
            ->callMax(1, $counter)
            ->mapUntil('is_string', static fn(int $v): int => $v * 2)
            ->onlyIntegers()
            ->cache();
        
        $this->performTest089($stream1, $counter);
    }
    
    private function performTest089(Stream $stream1, Counter $counter): void
    {
        $expectedNumbers = [6, 10, 4, 4, 1, 8, 6, 2, 5];
        
        self::assertSame($expectedNumbers, $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->reduce(Reducers::sum()); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame(\array_sum($expectedNumbers), $stream2->get());
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream2->transform(static fn(int $sum): float => $sum / 2.0); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame(\array_sum($expectedNumbers) / 2.0, $stream3->get());
        self::assertSame(1, $counter->get());
        
        self::assertSame(\array_sum($expectedNumbers), $stream2->get());
        self::assertSame(1, $counter->get());
        
        $stream4 = $stream3->wrap([1, 2, 3, 'foo', 4, 5]); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame(\array_sum([2, 4, 6, 4, 5]) / 2.0, $stream4->get()); //iteration
        self::assertSame(2, $counter->get());
        
        self::assertSame(\array_sum($expectedNumbers) / 2.0, $stream3->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame(\array_sum($expectedNumbers), $stream2->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame(\count($expectedNumbers), $stream1->count()->get());
        self::assertSame(2, $counter->get());
    }
    
    public function test_prototype_shuffleAll(): void
    {
        $this->performTest090(false);
    }
    
    public function test_prototype_shuffleAll_with_onerror_handler(): void
    {
        $this->performTest090(true);
    }
    
    private function performTest090(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 1, 'c', 'd', 2, 'e'])
            ->callOnce($counter)
            ->onlyStrings()
            ->shuffle();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame(5, $stream1->count()->get()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame(5, \strlen($stream1->toString(''))); //iteration
        self::assertSame(2, $counter->get());
        
        $stream2 = $stream1->join(['f', 4, 'g']); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame(7, $stream2->count()->get()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream3 = $stream1->reduce(Reducers::concat())->transform('strlen'); //new stream
        self::assertNotSame($stream1, $stream3);
        
        self::assertSame(5, $stream3->get()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream4 = $stream3->wrap(['p', 'o', 'q', 1]); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame(3, $stream4->get()); //iteration
        self::assertSame(5, $counter->get());
        
        self::assertSame(5, $stream3->get());
        self::assertSame(5, $counter->get());
        
        self::assertSame(7, $stream2->count()->get()); //iteration
        self::assertSame(6, $counter->get());
        
        self::assertSame(5, $stream1->count()->get()); //iteration
        self::assertSame(7, $counter->get());
    }
    
    public function test_prototype_shuffleAll_cache(): void
    {
        $this->performTest091(false);
    }
    
    public function test_prototype_shuffleAll_cache_with_onerror_handler(): void
    {
        $this->performTest091(true);
    }
    
    private function performTest091(bool $onError): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype(['a', 'b', 1, 'c', 'd', 2, 'e'])
            ->callOnce($counter)
            ->onlyStrings()
            ->cache()
            ->shuffle();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame(5, $stream1->count()->get()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame(5, \strlen($stream1->toString('')));
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->join(['f', 4, 'g']); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame(7, $stream2->count()->get()); //iteration
        self::assertSame(2, $counter->get());
        
        self::assertSame(5, $stream1->count()->get());
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream1->reduce(Reducers::concat())->transform('strlen'); //new stream
        self::assertNotSame($stream1, $stream3);
        
        self::assertSame(5, $stream3->get()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream3->wrap(['p', 'o', 'q', 1]); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame(3, $stream4->get()); //iteration
        self::assertSame(3, $counter->get());
        
        self::assertSame(5, $stream3->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame(7, $stream2->count()->get());
        self::assertSame(3, $counter->get());
        
        self::assertSame(5, $stream1->count()->get());
        self::assertSame(3, $counter->get());
    }
    
    public function test_prototype_collect(): void
    {
        $this->performTest092(false);
    }
    
    public function test_prototype_collect_with_onerror_handler(): void
    {
        $this->performTest092(true);
    }
    
    private function performTest092(bool $onError): void
    {
        $counter = Consumers::counter();
        $stream1 = Stream::prototype([-5, 2, -1, 'foo', 1, -3, 2, 'bar', -1])->callOnce($counter)->onlyIntegers();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream1 = $stream1->collectValues();
        
        self::assertSame([-5, 2, -1, 1, -3, 2, -1], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->transform(Mappers::forEach(static fn(int $n): int => $n * 2)); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([-10, 4, -2, 2, -6, 4, -2], $stream2->toArray());
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream2->wrap([-2, 0, 5]); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame([-4, 0, 10], $stream3->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream3->transform(Mappers::forEach(static fn(int $v): int => $v + 2)); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame([0, 2, 7], $stream4->toArray());
        self::assertSame(2, $counter->get());
        
        $stream5 = $stream4->stream()->join([0, 1, 2]); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame([0, 2, 7, 0, 1, 2], $stream5->toArray());
        self::assertSame(2, $counter->get());
        
        self::assertSame([0, 2, 7], $stream4->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame([-4, 0, 10], $stream3->toArray());
        self::assertSame(2, $counter->get());
        
        self::assertSame([-10, 4, -2, 2, -6, 4, -2], $stream2->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame([-5, 2, -1, 1, -3, 2, -1], $stream1->get());
        self::assertSame(2, $counter->get());
    }
    
    public function test_prototype_collectKeys(): void
    {
        $this->performTest101(false);
    }
    
    public function test_prototype_collectKeys_with_onerror_handler(): void
    {
        $this->performTest101(true);
    }
    
    private function performTest101(bool $onError): void
    {
        $stream1 = Stream::prototype([-5, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter = Consumers::counter())
            ->onlyIntegers();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        $stream1 = $stream1->collectKeys();
        
        self::assertSame([0, 1, 2, 4, 5, 6, 8], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->transform(Mappers::forEach(static fn(int $n): int => $n * 2)); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([0, 2, 4, 8, 10, 12, 16], $stream2->toArray());
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream2->wrap([-2, 'a', 0, 'b', 5]); //new stream
        self::assertNotSame($stream2, $stream3);
        
        self::assertSame([0, 4, 8], $stream3->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream3->transform(Mappers::forEach(static fn(int $v): int => $v + 2)); //new stream
        self::assertNotSame($stream3, $stream4);
        
        self::assertSame([2, 4, 6], $stream4->toArray());
        self::assertSame(2, $counter->get());
        
        $stream5 = $stream4->stream()->join(['c', 1, 'd', 2])->onlyIntegers(); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame([2, 4, 6, 1, 2], $stream5->toArray());
        self::assertSame(2, $counter->get());
        
        self::assertSame([2, 4, 6], $stream4->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame([0, 4, 8], $stream3->toArray());
        self::assertSame(2, $counter->get());
        
        self::assertSame([0, 2, 4, 8, 10, 12, 16], $stream2->get());
        self::assertSame(2, $counter->get());
        
        self::assertSame([0, 1, 2, 4, 5, 6, 8], $stream1->get());
        self::assertSame(2, $counter->get());
    }
    
    public function test_prototype_filterWhile_collect(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->filterWhile('is_int', Filters::greaterThan(0))
            ->onlyIntegers();
        
        $this->performTest093($stream1, $counter);
    }
    
    public function test_prototype_filterWhile_collect_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->filterWhile('is_int', Filters::greaterThan(0))
            ->onlyIntegers();
        
        $this->performTest093($stream1, $counter);
    }
    
    public function test_prototype_filterUntil_collect(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->filterUntil('is_string', Filters::greaterThan(0))
            ->onlyIntegers();
        
        $this->performTest093($stream1, $counter);
    }
    
    public function test_prototype_filterUntil_collect_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->filterUntil('is_string', Filters::greaterThan(0))
            ->onlyIntegers();
        
        $this->performTest093($stream1, $counter);
    }
    
    private function performTest093(Stream $stream1, Counter $counter): void
    {
        self::assertSame([3, 2, 1, -3, 2, -1], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->join([2, -1, 3, 'zoo', 4, -2]); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([3, 2, 1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream1->filter(Filters::lessThan(0)); //new stream
        self::assertNotSame($stream3, $stream1);
        
        self::assertSame([-3, -1], $stream3->toArray()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream4 = $stream2->reduce(Reducers::minMax()); //new stream
        self::assertNotSame($stream2, $stream4);
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream5 = $stream4->wrap([-5, 2, 3, -4, 0, 1, 5]); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame(['min' => 1, 'max' => 5], $stream5->get()); //iteration
        self::assertSame(5, $counter->get());
        
        //new stream
        $stream6 = $stream1->collectValues()->transform(Mappers::forEach(static fn(int $n): int => $n * 2));
        self::assertNotSame($stream1, $stream6);
        
        self::assertSame([6, 4, 2, -6, 4, -2], $stream6->get()); //iteration
        self::assertSame(6, $counter->get());
        
        $stream7 = $stream6->transform(Reducers::count()); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame(6, $stream7->get());
        self::assertSame(6, $counter->get());
        
        $stream8 = $stream6->wrap([2, 3, 5, -1, 4]); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame([4, 6, 10, 8], $stream8->get()); //iteration
        self::assertSame(7, $counter->get());
        
        self::assertSame(6, $stream7->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame([6, 4, 2, -6, 4, -2], $stream6->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame(['min' => 1, 'max' => 5], $stream5->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame([-3, -1], $stream3->toArray()); //iteration
        self::assertSame(8, $counter->get());
        
        self::assertSame([3, 2, 1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray()); //iteration
        self::assertSame(9, $counter->get());
        
        self::assertSame([3, 2, 1, -3, 2, -1], $stream1->toArray()); //iteration
        self::assertSame(10, $counter->get());
    }
    
    public function test_prototype_filterWhile_collect_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->filterWhile('is_int', Filters::greaterThan(0))
            ->onlyIntegers()
            ->cache();
        
        $this->performTest094($stream1, $counter);
    }
    
    public function test_prototype_filterWhile_collect_cache_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->filterWhile('is_int', Filters::greaterThan(0))
            ->onlyIntegers()
            ->cache();
        
        $this->performTest094($stream1, $counter);
    }
    
    public function test_prototype_filterUntil_collect_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->filterUntil('is_string', Filters::greaterThan(0))
            ->onlyIntegers()
            ->cache();
        
        $this->performTest094($stream1, $counter);
    }
    
    public function test_prototype_filterUntil_collect_cache_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->filterUntil('is_string', Filters::greaterThan(0))
            ->onlyIntegers()
            ->cache();
        
        $this->performTest094($stream1, $counter);
    }
    
    private function performTest094(Stream $stream1, Counter $counter): void
    {
        self::assertSame([3, 2, 1, -3, 2, -1], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->join([2, -1, 3, 'zoo', 4, -2]); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([3, 2, 1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream1->filter(Filters::lessThan(0)); //new stream
        self::assertNotSame($stream3, $stream1);
        
        self::assertSame([-3, -1], $stream3->toArray());
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream2->reduce(Reducers::minMax()); //new stream
        self::assertNotSame($stream2, $stream4);
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get());
        self::assertSame(2, $counter->get());
        
        $stream5 = $stream4->wrap([-5, 2, 3, -4, 0, 1, 5]); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame(['min' => 1, 'max' => 5], $stream5->get()); //iteration
        self::assertSame(3, $counter->get());
        
        //new stream
        $stream6 = $stream1->collectValues()->transform(Mappers::forEach(static fn(int $n): int => $n * 2));
        self::assertNotSame($stream1, $stream6);
        
        self::assertSame([6, 4, 2, -6, 4, -2], $stream6->get());
        self::assertSame(3, $counter->get());
        
        $stream7 = $stream6->transform(Reducers::count()); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame(6, $stream7->get());
        self::assertSame(3, $counter->get());
        
        $stream8 = $stream6->wrap([2, 3, 5, -1, 4]); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame([4, 6, 10, 8], $stream8->get()); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame(6, $stream7->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame([6, 4, 2, -6, 4, -2], $stream6->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame(['min' => 1, 'max' => 5], $stream5->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame([-3, -1], $stream3->toArray());
        self::assertSame(4, $counter->get());
        
        self::assertSame([3, 2, 1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray());
        self::assertSame(4, $counter->get());
        
        self::assertSame([3, 2, 1, -3, 2, -1], $stream1->toArray());
        self::assertSame(4, $counter->get());
    }
    
    public function test_prototype_skipWhile(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->skipWhile('is_int')
            ->onlyIntegers();
        
        $this->performTest095($stream1, $counter);
    }
    
    public function test_prototype_skipWhile_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->skipWhile('is_int')
            ->onlyIntegers();
        
        $this->performTest095($stream1, $counter);
    }
    
    public function test_prototype_skipUntil(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->skipUntil('is_string')
            ->onlyIntegers();
        
        $this->performTest095($stream1, $counter);
    }
    
    public function test_prototype_skipUntil_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->skipUntil('is_string')
            ->onlyIntegers();
        
        $this->performTest095($stream1, $counter);
    }
    
    private function performTest095(Stream $stream1, Counter $counter): void
    {
        self::assertSame([1, -3, 2, -1], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->join([2, -1, 3, 'zoo', 4, -2]); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream1->filter(Filters::lessThan(0)); //new stream
        self::assertNotSame($stream3, $stream1);
        
        self::assertSame([-3, -1], $stream3->toArray()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream4 = $stream2->reduce(Reducers::minMax()); //new stream
        self::assertNotSame($stream2, $stream4);
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream5 = $stream4->wrap([-5, 2, 3, 'foo', -4, 0, 1, 5]); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame(['min' => -4, 'max' => 5], $stream5->get()); //iteration
        self::assertSame(5, $counter->get());
        
        //new stream
        $stream6 = $stream1->collectValues()->transform(Mappers::forEach(static fn(int $n): int => $n * 2));
        self::assertNotSame($stream1, $stream6);
        
        self::assertSame([2, -6, 4, -2], $stream6->get()); //iteration
        self::assertSame(6, $counter->get());
        
        $stream7 = $stream6->transform(Reducers::count()); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame(4, $stream7->get());
        self::assertSame(6, $counter->get());
        
        $stream8 = $stream6->wrap([2, 3, 'foo', 5, -1, 4]); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame([10, -2, 8], $stream8->get()); //iteration
        self::assertSame(7, $counter->get());
        
        self::assertSame(4, $stream7->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame([2, -6, 4, -2], $stream6->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame(['min' => -4, 'max' => 5], $stream5->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame([-3, -1], $stream3->toArray()); //iteration
        self::assertSame(8, $counter->get());
        
        self::assertSame([1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray()); //iteration
        self::assertSame(9, $counter->get());
        
        self::assertSame([1, -3, 2, -1], $stream1->toArray()); //iteration
        self::assertSame(10, $counter->get());
    }
    
    public function test_prototype_skipWhile_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->skipWhile('is_int')
            ->onlyIntegers()
            ->cache();
        
        $this->performTest096($stream1, $counter);
    }
    
    public function test_prototype_skipWhile_cache_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->skipWhile('is_int')
            ->onlyIntegers()
            ->cache();
        
        $this->performTest096($stream1, $counter);
    }
    
    public function test_prototype_skipUntil_cache(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->callOnce($counter)
            ->skipUntil('is_string')
            ->onlyIntegers()
            ->cache();
        
        $this->performTest096($stream1, $counter);
    }
    
    public function test_prototype_skipUntil_cache_with_onerror_handler(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([-5, 3, 2, -1, 'foo', 1, -3, 2, 'bar', -1])
            ->onError(OnError::abort())
            ->callOnce($counter)
            ->skipUntil('is_string')
            ->onlyIntegers()
            ->cache();
        
        $this->performTest096($stream1, $counter);
    }
    
    private function performTest096(Stream $stream1, Counter $counter): void
    {
        self::assertSame([1, -3, 2, -1], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->join([2, -1, 3, 'zoo', 4, -2]); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream3 = $stream1->filter(Filters::lessThan(0)); //new stream
        self::assertNotSame($stream3, $stream1);
        
        self::assertSame([-3, -1], $stream3->toArray());
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream2->reduce(Reducers::minMax()); //new stream
        self::assertNotSame($stream2, $stream4);
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get());
        self::assertSame(2, $counter->get());
        
        $stream5 = $stream4->wrap([-5, 2, 3, 'foo', -4, 0, 1, 5]); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame(['min' => -4, 'max' => 5], $stream5->get()); //iteration
        self::assertSame(3, $counter->get());
        
        //new stream
        $stream6 = $stream1->collectValues()->transform(Mappers::forEach(static fn(int $n): int => $n * 2));
        self::assertNotSame($stream1, $stream6);
        
        self::assertSame([2, -6, 4, -2], $stream6->get());
        self::assertSame(3, $counter->get());
        
        $stream7 = $stream6->transform(Reducers::count()); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame(4, $stream7->get());
        self::assertSame(3, $counter->get());
        
        $stream8 = $stream6->wrap([2, 3, 'foo', 5, -1, 4]); //new stream
        self::assertNotSame($stream6, $stream7);
        
        self::assertSame([10, -2, 8], $stream8->get()); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame(4, $stream7->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame([2, -6, 4, -2], $stream6->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame(['min' => -4, 'max' => 5], $stream5->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame(['min' => -3, 'max' => 4], $stream4->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame([-3, -1], $stream3->toArray());
        self::assertSame(4, $counter->get());
        
        self::assertSame([1, -3, 2, -1, 2, -1, 3, 4, -2], $stream2->toArray());
        self::assertSame(4, $counter->get());
        
        self::assertSame([1, -3, 2, -1], $stream1->toArray());
        self::assertSame(4, $counter->get());
    }
    
    public function test_prototype_volatileSkip(): void
    {
        $this->performTest097(false);
    }
    
    public function test_prototype_volatileSkip_with_onerror_handler(): void
    {
        $this->performTest097(true);
    }
    
    private function performTest097(bool $onError): void
    {
        $skip = Memo::value();
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 1, 4, 6, 2, 8, 5])
            ->callOnce($counter)
            ->callOnce($skip)
            ->skip(static fn(): int => $skip->read() + 1);
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame([6, 2, 8, 5], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame('6,2,8,5', $stream1->toString()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream2 = $stream1->filter(Filters::number()->isEven()); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([4 => 6, 2, 8], $stream2->toArrayAssoc()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream3 = $stream1->wrap([2, 8, 3, 1, 4, 5]); //new stream
        self::assertNotSame($stream1, $stream3);
        
        self::assertSame([1, 4, 5], $stream3->toArray()); //iteration
        self::assertSame(4, $counter->get());
        
        $stream4 = $stream2->join([1, 4, 3]); //new stream
        self::assertNotSame($stream2, $stream4);
        
        self::assertSame([6, 2, 8, 4], $stream4->toArray()); //iteration
        self::assertSame(5, $counter->get());
        
        $stream5 = $stream4->reduce(Reducers::sum())->transform(static fn(int $v): int => $v / 2); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame(10, $stream5->get()); //iteration
        self::assertSame(6, $counter->get());
        
        $stream6 = $stream5->wrap([4, 5, 2, 7, 1, 3, 2, 5, 8]); //new stream
        self::assertNotSame($stream5, $stream6);
        
        self::assertSame(5, $stream6->get()); //iteration
        self::assertSame(7, $counter->get());
        
        self::assertSame(10, $stream6->transform(null)->get()); //new stream
        self::assertSame(7, $counter->get());
        
        self::assertSame(10, $stream5->get());
        self::assertSame(7, $counter->get());
        
        self::assertSame([6, 2, 8, 4], $stream4->toArray()); //iteration
        self::assertSame(8, $counter->get());
        
        self::assertSame([1, 4, 5], $stream3->toArray()); //iteration
        self::assertSame(9, $counter->get());
        
        self::assertSame([4 => 6, 2, 8], $stream2->toArrayAssoc()); //iteration
        self::assertSame(10, $counter->get());
        
        self::assertSame([6, 2, 8, 5], $stream1->toArray()); //iteration
        self::assertSame(11, $counter->get());
    }
    
    public function test_prototype_volatileSkip_cache(): void
    {
        $this->performTest098(false);
    }
    
    public function test_prototype_volatileSkip_cache_with_onerror_handler(): void
    {
        $this->performTest098(true);
    }
    
    private function performTest098(bool $onError): void
    {
        $skip = Memo::value();
        $counter = Consumers::counter();
        
        $stream1 = Stream::prototype([3, 5, 1, 4, 6, 2, 8, 5])
            ->callOnce($counter)
            ->callOnce($skip)
            ->skip(static fn(): int => $skip->read() + 1)
            ->cache();
        
        if ($onError) {
            $stream1 = $stream1->onError(OnError::abort());
        }
        
        self::assertSame([6, 2, 8, 5], $stream1->toArray()); //iteration
        self::assertSame(1, $counter->get());
        
        self::assertSame('6,2,8,5', $stream1->toString());
        self::assertSame(1, $counter->get());
        
        $stream2 = $stream1->filter(Filters::number()->isEven()); //new stream
        self::assertNotSame($stream1, $stream2);
        
        self::assertSame([4 => 6, 2, 8], $stream2->toArrayAssoc());
        self::assertSame(1, $counter->get());
        
        $stream3 = $stream1->wrap([2, 8, 3, 1, 4, 5]); //new stream
        self::assertNotSame($stream1, $stream3);
        
        self::assertSame([1, 4, 5], $stream3->toArray()); //iteration
        self::assertSame(2, $counter->get());
        
        $stream4 = $stream2->join([1, 4, 3]); //new stream
        self::assertNotSame($stream2, $stream4);
        
        self::assertSame([6, 2, 8, 4], $stream4->toArray()); //iteration
        self::assertSame(3, $counter->get());
        
        $stream5 = $stream4->reduce(Reducers::sum())->transform(static fn(int $v): int => $v / 2); //new stream
        self::assertNotSame($stream4, $stream5);
        
        self::assertSame(10, $stream5->get());
        self::assertSame(3, $counter->get());
        
        $stream6 = $stream5->wrap([4, 5, 2, 7, 1, 3, 2, 5, 8]); //new stream
        self::assertNotSame($stream5, $stream6);
        
        self::assertSame(5, $stream6->get()); //iteration
        self::assertSame(4, $counter->get());
        
        self::assertSame(10, $stream6->transform(null)->get()); //new stream
        self::assertSame(4, $counter->get());
        
        self::assertSame(10, $stream5->get());
        self::assertSame(4, $counter->get());
        
        self::assertSame([6, 2, 8, 4], $stream4->toArray());
        self::assertSame(4, $counter->get());
        
        self::assertSame([1, 4, 5], $stream3->toArray());
        self::assertSame(4, $counter->get());
        
        self::assertSame([4 => 6, 2, 8], $stream2->toArrayAssoc());
        self::assertSame(4, $counter->get());
        
        self::assertSame([6, 2, 8, 5], $stream1->toArray());
        self::assertSame(4, $counter->get());
    }
}