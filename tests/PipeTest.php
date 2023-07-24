<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Accumulate;
use FiiSoft\Jackdaw\Operation\CollectIn;
use FiiSoft\Jackdaw\Operation\Filter;
use FiiSoft\Jackdaw\Operation\Gather;
use FiiSoft\Jackdaw\Operation\Internal\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\Limit;
use FiiSoft\Jackdaw\Operation\Map;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Reverse;
use FiiSoft\Jackdaw\Operation\Shuffle;
use FiiSoft\Jackdaw\Operation\Sort;
use FiiSoft\Jackdaw\Operation\SortLimited;
use FiiSoft\Jackdaw\Operation\Tail;
use FiiSoft\Jackdaw\Operation\Terminating\First;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\IsEmpty;
use FiiSoft\Jackdaw\Operation\Terminating\Last;
use FiiSoft\Jackdaw\Operation\Unique;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class PipeTest extends TestCase
{
    public function test_pipe(): void
    {
        //given
        $collector = Collectors::values();
        $signal = new Signal(Stream::empty());
        
        $stream = Stream::empty();
        $pipe = new Pipe();
        $pipe->chainOperation(new Filter(Filters::greaterThan(5)), $stream);
        $pipe->chainOperation(new CollectIn($collector), $stream);
        $pipe->chainOperation(new Limit(5), $stream);
        $pipe->prepare();
        
        //when
        foreach ([2, 8, 4, 1, 5, 2, 6, 9, 2, 0, 7, 3, 6, 4, 8, 2, 9, 2, 8] as $key => $value) {
            $signal->item->key = $key;
            $signal->item->value = $value;
            
            $pipe->head->handle($signal);
            
            if ($signal->isEmpty) {
                break;
            }
        }
        
        //then
        self::assertSame([8, 6, 9, 7, 6], $collector->getData());
    }
    
    public function test_pipe_cannot_be_cloned_when_its_stack_is_not_empty(): void
    {
        //Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot clone Pipe with non-empty stack');
        
        //Arrange
        $pipe = new Pipe();
        $pipe->stack[] = new Limit(1);
        
        //Act
        $method = (new \ReflectionObject($pipe))->getMethod('__clone');
        $method->setAccessible(true);
        $method->invoke($pipe);
    }
    
    public function test_chain_Sort_Last(): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, new Sort(), new Last($stream));
        
        //then
        $this->assertPipeContainsOperations($pipe, SortLimited::class, First::class);
    }
    
    public function test_chain_SortLimited_Reverse(): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, new SortLimited(1), new Reverse());
        
        //then
        $this->assertPipeContainsOperations($pipe, SortLimited::class);
    }
    
    public function test_chain_Reverse_Tail(): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, new Reverse(), new Tail(6));
        
        //then
        $this->assertPipeContainsOperations($pipe, Limit::class, Reverse::class);
    }
    
    public function test_chain_Shuffle_Reverse(): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, new Shuffle(), new Reverse());
        
        //then
        $this->assertPipeContainsOperations($pipe, Shuffle::class);
    }
    
    public function test_chain_Reverse_Shuffle(): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, new Reverse(), new Shuffle());
        
        //then
        $this->assertPipeContainsOperations($pipe, Shuffle::class);
    }
    
    public function test_chain_Sort_Tail(): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, new Sort(), new Tail(5));
        
        //then
        $this->assertPipeContainsOperations($pipe, SortLimited::class, Reverse::class);
    }
    
    public function test_stacked_sort_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $last = new Last($stream);
        $this->addOperations($pipe, new Sort(), $last);

        //when
        $this->sendToPipe([6, 2, 3, 8, 1, 9], $pipe, $signal);

        //then
        self::assertSame(9, $last->get());
    }

    public function test_stacked_tail_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $last = new Last($stream);
        $this->addOperations($pipe, new Tail(4), $last);

        //when
        $this->sendToPipe([6, 2, 3, 8, 1, 9], $pipe, $signal);

        //then
        self::assertSame(9, $last->get());
    }

    /**
     * @dataProvider getOperationNames
     */
    public function test_stacked_isEmpty(string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $isEmpty = new IsEmpty($stream, true);
        $this->addOperations($pipe, $this->createOperation($operation), $isEmpty);

        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);

        //then
        self::assertFalse($isEmpty->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    public function test_stacked_hasOnly(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasOnly = new HasOnly($stream, [2, 3], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasOnly);
        
        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);
        
        //then
        self::assertFalse($hasOnly->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    public function test_stacked_hasEvery(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasEvery = new HasEvery($stream, [2, 4], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasEvery);
        
        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);
        
        //then
        self::assertFalse($hasEvery->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    public function test_stacked_has(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $hasEvery = new HasEvery($stream, [2, 4], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasEvery);

        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);

        //then
        self::assertFalse($hasEvery->get());
    }
    
    public function getOperationNames(): array
    {
        return [
            ['gather'],
            ['sort'],
            ['reverse'],
        ];
    }
    
    public function createAllOperationModeVariations(): \Generator
    {
        foreach (['gather', 'sort', 'reverse'] as $operation) {
            foreach ([Check::VALUE, Check::KEY, Check::ANY, Check::BOTH] as $mode) {
                yield [$mode, $operation];
            }
        }
    }
    
    public function test_pipe_forget(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $sort = new Sort();
        $accumulate = new Accumulate('is_string');
        $filter = new Filter('is_int');
        
        $this->addOperations($pipe, $sort, $accumulate, $filter);
        $pipe->prepare();
        
        self::assertInstanceOf(Sort::class, $pipe->head);
        $this->assertPipeContainsOperations($pipe, Sort::class, Accumulate::class, Filter::class);
        
        $signal->forget($accumulate);
        self::assertInstanceOf(Sort::class, $pipe->head);
        $this->assertPipeContainsOperations($pipe, Sort::class, Filter::class);
        
        //repeat to check if nothing wrong happend
        $signal->forget($accumulate);
        self::assertInstanceOf(Sort::class, $pipe->head);
        $this->assertPipeContainsOperations($pipe, Sort::class, Filter::class);
        
        $signal->forget($sort);
        $this->assertPipeContainsOperations($pipe, Filter::class);
        self::assertInstanceOf(Filter::class, $pipe->head);
        
        $signal->forget($filter);
        self::assertInstanceOf(Ending::class, $pipe->head);
    }
    
    public function test_forget_with_operation_put_in_stack(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $filter = new Filter('is_int');
        $sort = new Sort();
        $map = new Map(Mappers::trim());
        
        $this->addOperations($pipe, $filter, $sort, $map);
        $pipe->prepare();
        
        $signal->continueWith(Producers::from([3, 4]), $sort);
        self::assertSame([$filter], $pipe->stack);
        
        //when
        $signal->forget($filter);
        
        //then
        self::assertSame([], $pipe->stack);
        $this->assertPipeContainsOperations($pipe, Sort::class, Map::class);
    }
    
    public function test_chain_Reverse_Unique_Shuffle_Filter(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        //when
        $this->chainOperations($pipe, $stream, new Reverse(), new Unique(), new Shuffle(), new Filter('is_string'));
        
        //then
        $this->assertPipeContainsOperations($pipe, Filter::class, Unique::class, Shuffle::class);
    }
    
    private function createOperation(string $name): Operation
    {
        switch ($name) {
            case 'gather': return new Gather();
            case 'sort': return new Sort();
            case 'reverse': return new Reverse();
            default:
                throw new \InvalidArgumentException('Cannot create operation '.$name);
        }
    }
    
    private function sendToPipe(array $data, Pipe $pipe, Signal $signal): void
    {
        $pipe->prepare();
        
        $item = $signal->item;
        foreach ($data as $item->key => $item->value) {
            $pipe->head->handle($signal);
            
            if ($signal->isEmpty) {
                return;
            }
        }
        
        $signal->streamIsEmpty();
        $pipe->head->streamingFinished($signal);
    }
    
    private function assertPipeContainsOperations(Pipe $pipe, string ...$classes): void
    {
        $next = $pipe->head;
        if ($next instanceof Initial) {
            $next = $next->getNext();
        }
        
        foreach ($classes as $expectedClass) {
            self::assertInstanceOf($expectedClass, $next);
            $next = $next->getNext();
        }
        
        self::assertInstanceOf(Ending::class, $next);
    }
    
    private function chainOperations(Pipe $pipe, Stream $stream, Operation ...$operations): void
    {
        foreach ($operations as $operation) {
            $pipe->chainOperation($operation, $stream);
        }
    }
    
    private function addOperations(Pipe $pipe, Operation ...$operations): void
    {
        foreach ($operations as $operation) {
            $pipe->append($operation);
        }
    }
    
    /**
     * @return array{Stream, Pipe, Signal}
     */
    private function prepare(): array
    {
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        $signal = $this->getSignalFromStream($stream);
        
        return [$stream, $pipe, $signal];
    }
    
    private function getPipeFromStream(Stream $stream): Pipe
    {
        return $this->getPropertyFromObject($stream, 'pipe');
    }
    
    private function getSignalFromStream(Stream $stream): Signal
    {
        return $this->getPropertyFromObject($stream, 'signal');
    }
    
    private function getPropertyFromObject(object $object, string $property)
    {
        $prop = (new \ReflectionObject($object))->getProperty($property);
        $prop->setAccessible(true);
        
        return $prop->getValue($object);
    }
}
