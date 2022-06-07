<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Aggregate;
use FiiSoft\Jackdaw\Operation\Flat;
use FiiSoft\Jackdaw\Operation\Internal\Ending;
use FiiSoft\Jackdaw\Operation\Internal\FeedMany;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\MapKey;
use FiiSoft\Jackdaw\Operation\MapKeyValue;
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
    
    public function test_MapKey_can_merge_Value_with_FieldValue(): void
    {
        $first = new MapKey(Mappers::value());
        $second = new MapKey(Mappers::fieldValue('foo'));
        
        self::assertTrue($first->mergeWith($second));
        self::assertFalse($first->mergeWith(new MapKey(Mappers::simple('bar'))));
    }
    
    public function test_MapKey_can_merge_FieldValue_with_Value(): void
    {
        $first = new MapKey(Mappers::fieldValue('foo'));
        $second = new MapKey(Mappers::value());
        
        self::assertTrue($first->mergeWith($second));
    }
    
    public function test_MapKeyValue_throws_exception_when_number_of_arguments_accepted_by_mapper_is_invalid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('KeyValue mapper have to accept 0, 1 or 2 arguments, but requires 3');
        
        new MapKeyValue(static fn($a, $b, $c): bool => true);
    }
    
    /**
     * @dataProvider getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray
     */
    public function test_MapKeyValue_throws_exception_when_return_type_of_mapper_is_not_array(callable $mapper): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('KeyValue mapper must have declared array as its return type');
        
        new MapKeyValue($mapper);
    }
    
    public function getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray(): \Generator
    {
        $mappers = [
            static fn(): bool => true,
            static function (): string {
                return 'wrong';
            },
            static fn() => [],
            static function () {
                return [];
            }
        ];
    
        foreach ($mappers as $mapper) {
            yield [$mapper];
        }
    }
}