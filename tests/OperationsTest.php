<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Aggregate;
use FiiSoft\Jackdaw\Operation\Flat;
use FiiSoft\Jackdaw\Operation\Internal\Ending;
use FiiSoft\Jackdaw\Operation\Internal\FeedMany;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\SendToMax;
use FiiSoft\Jackdaw\Operation\SortLimited;
use FiiSoft\Jackdaw\Operation\State\SortLimited\BufferFull;
use FiiSoft\Jackdaw\Operation\Tail;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;
use SplMaxHeap;
use stdClass;

final class OperationsTest extends TestCase
{
    public function test_Tail_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param length');
        
        new Tail(0);
    }
    
    public function test_SendToMax_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param times');
        
        new SendToMax(0, Consumers::counter());
    }
    
    /**
     * @dataProvider getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid
     */
    public function test_Aggregate_throws_exception_when_param_keys_is_invalid($keys): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param keys');
        
        new Aggregate($keys);
    }
    
    public function getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid(): array
    {
        return [
            [[]],
            [[true]],
            [[new stdClass()]],
            [[12.0]],
            [['']],
            [[[]]],
        ];
    }
    
    public function test_Flat_has_limit_of_levels(): void
    {
        $flat = new Flat();
        $maxLevel = $flat->maxLevel();
        
        $flat->mergeWith(new Flat());
        self::assertSame($maxLevel, $flat->maxLevel());
    }
    
    public function test_Flat_does_not_pass_signal_when_iterable_is_empty(): void
    {
        $counter = Consumers::counter();
        Stream::from([[]])->flat()->call($counter)->run();
        
        self::assertSame(0, $counter->count());
    }
    
    public function test_MapFieldWhen_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
        
        new MapFieldWhen('', 'is_string', 'strtolower');
    }
    
    public function test_SortLimited_throws_exception_when_param_limit_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param limit');
        
        new SortLimited(0);
    }
    
    public function test_change_size_of_SortLimited_buffer_is_prohibited(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Change of size of full buffer is prohibited');
        
        $state = new BufferFull(new SortLimited(5), new SplMaxHeap());
        $state->setLength(1);
    }
    
    public function test_Ending_operation(): void
    {
        $operation = new Ending();
    
        /* @var $stream Stream */
        $stream = Stream::empty();
        self::assertFalse($operation->streamingFinished(new Signal($stream)));
        
        self::assertFalse($operation->isLazy());
    }
    
    public function test_Ending_operation_cannot_be_removed_from_chain(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Ending::removeFromChain)');
    
        $operation = new Ending();
        $operation->removeFromChain();
    }
    
    public function test_Ending_operation_have_to_be_the_last_operation_in_chain(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Ending::setNext)');
    
        $operation = new Ending();
        $operation->setNext(new Ending());
    }
    
    public function test_Initial_operation_have_to_be_first_operation_in_chain(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Inital::setPrev)');
    
        $operation = new Initial();
        $operation->setPrev(new Ending());
    }
    
    public function test_FeedMany_throws_exception_when_no_streams_are_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FeedMany requires at least one stream');
        
        new FeedMany();
    }
}