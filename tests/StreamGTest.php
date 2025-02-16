<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Inspector\SequenceIsEmpty;
use FiiSoft\Jackdaw\Memo\Inspector\SequenceIsFull;
use FiiSoft\Jackdaw\Memo\Inspector\SequenceLengthIs;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Special\Assert\AssertionFailed;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use PHPUnit\Framework\TestCase;

final class StreamGTest extends TestCase
{
    private const CONSTANT_LENGTH_SEQUENCE = [
        '+', 5, 2, '-', 8, 3,
        ':', 9, 3, //omit
        '+', 2, 4, '*', 3, 6, '-', 5, 2
    ];
    
    private const CONSTANT_LENGTH_RESULT = [
        '+' => [7, 6],
        '-' => [5, 3],
        '*' => [18],
    ];
    
    private const VARIABLE_LENGTH_SEQUENCE = [
        '+', 5, 2, 4, '-', 8, 3, '+', 2, 4, '*', 3, 6, 2,
        ':', 18, 2, //omit
        '-', 5, 2, 2, '+', 9, 3, 5,
        ':', 24, 3, 2, //omit
        '-', 15, 8, 3, 5
    ];
    
    private const VARIABLE_LENGTH_RESULT = [
        '+' => [11, 6, 17],
        '-' => [5, 1, -1],
        '*' => [36],
    ];
    
    private const FIND_SEQUENCES_DATA = [
        3, 'a', 'b', 2, 'c', 'd', 4, 'e', 'f', 'g', 3, 'o', 'p', 'q',
        1, 'h', 3, 'i', 'j', 'k', 2, 'l', 'm', 'n', 4, 'r', 's', 't', 'u',
    ];
    
    private const FIND_SEQUENCES_RESULT = [
        2 => [['c', 'd']],
        3 => [['o', 'p', 'q'], ['i', 'j', 'k']],
        1 => [['h']],
        4 => [['r', 's', 't', 'u']],
    ];
    
    private const VOLATILE_CHUNK_DATA = [3, 'a', 'b', 'c', 1, 'd', 2, 'e', 'f', 1, 'g', 4, 'h', 'i', 'j', 'k', 2, 'l'];
    
    private const VOLATILE_CHUNK_REINDEX_KEYS_RESULT = [
        4 => [['h', 'i', 'j', 'k']],
        3 => [['a', 'b', 'c']],
        2 => [['e', 'f']],
        1 => [['d'], ['g'], ['l']],
    ];
    
    private const VOLATILE_CHUNK_KEEP_KEYS_RESULT = [
        4 => [[12 => 'h', 'i', 'j', 'k']],
        3 => [[1 => 'a', 'b', 'c']],
        2 => [[7 => 'e', 'f']],
        1 => [[5 => 'd'], [10 => 'g'], [17 => 'l']],
    ];

    private const ROWSET = [
        ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
        ['id' => 9, 'name' => 'Chris', 'age' => 18, 'sex' => 'male'],
        ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
        ['id' => 5, 'name' => 'Chris', 'age' => 16, 'sex' => 'male'],
        ['id' => 6, 'name' => 'Joanna', 'age' => 15, 'sex' => 'female'],
        ['id' => 10, 'name' => 'Tom', 'age' => 35, 'sex' => 'male'],
        ['id' => 1, 'name' => 'Kila', 'age' => 18, 'sex' => 'female'],
    ];
    
    public function test_process_sequences_of_constant_length_using_chunks(): void
    {
        $result = Stream::from(self::CONSTANT_LENGTH_SEQUENCE)
            ->chunk(3, true)
            ->classifyBy(0)
            ->map(static function (array $chunk, string $operation): ?int {
                switch ($operation) {
                    case '+': return $chunk[1] + $chunk[2];
                    case '-': return $chunk[1] - $chunk[2];
                    case '*': return $chunk[1] * $chunk[2];
                    default: return null;
                }
            })
            ->omit(null)
            ->categorize(Discriminators::byKey())
            ->toArrayAssoc();
        
        self::assertSame(self::CONSTANT_LENGTH_RESULT, $result);
    }
    
    public function test_process_sequences_of_constant_length_using_memo(): void
    {
        $seq = Memo::sequence(3);
        
        $result = Stream::from(self::CONSTANT_LENGTH_SEQUENCE)
            ->remember($seq)
            ->filter($seq->inspect(new SequenceIsFull()))
            ->classify($seq->value(0))
            ->map(static function ($_, string $operation) use ($seq): ?int {
                switch ($operation) {
                    case '+': return $seq->valueOf(1) + $seq->valueOf(2);
                    case '-': return $seq->valueOf(1) - $seq->valueOf(2);
                    case '*': return $seq->valueOf(1) * $seq->valueOf(2);
                    default: return null;
                }
            })
            ->call(static function () use ($seq) {
                $seq->clear();
            })
            ->omit(null)
            ->categorize(Discriminators::byKey())
            ->toArrayAssoc();
        
        self::assertSame(self::CONSTANT_LENGTH_RESULT, $result);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_1(): void
    {
        $sequence = Memo::sequence();
        
        $nextSequenceDetector = static function ($value): bool {
            static $isNext = false;
            if (\is_string($value)) {
                if ($isNext) {
                    return true;
                }
                $isNext = true;
            }
            return false;
        };
        
        $handler = static function () use ($sequence): ?int {
            switch ($sequence->remove(0)->value) {
                case '+': $reducer = static fn(int $acc, int $val): int => $acc + $val; break;
                case '-': $reducer = static fn(int $acc, int $val): int => $acc - $val; break;
                case '*': $reducer = static fn(int $acc, int $val): int => $acc * $val; break;
                default: $reducer = null;
            }
            
            $nextOperation = $sequence->remove(-1);
            try {
                return $reducer !== null ? $sequence->reduce($reducer) : null;
            } finally {
                $sequence->clear();
                $sequence->write($nextOperation->value, $nextOperation->key);
            }
        };
        
        $result = Stream::from(self::VARIABLE_LENGTH_SEQUENCE)->join(['end'])
            ->remember($sequence)
            ->filter($nextSequenceDetector)
            ->classify($sequence->value(0))
            ->map($handler)
            ->omit(null)
            ->categorize(Discriminators::byKey())
            ->toArrayAssoc();
        
        self::assertSame(self::VARIABLE_LENGTH_RESULT, $result);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_2(): void
    {
        $sequence = Memo::sequence();
        $nextOperation = Memo::full();
        
        $nextSequenceDetector = static function ($value): bool {
            static $isNext = false;
            if (\is_string($value)) {
                if ($isNext) {
                    return true;
                }
                $isNext = true;
            }
            return false;
        };
        
        $mapper = static function ($_, string $operation) use ($sequence): ?int {
            switch ($operation) {
                case '+': $reducer = static fn(int $acc, int $val): int => $acc + $val; break;
                case '-': $reducer = static fn(int $acc, int $val): int => $acc - $val; break;
                case '*': $reducer = static fn(int $acc, int $val): int => $acc * $val; break;
                default: $reducer = null;
            }
            
            return $reducer !== null ? $sequence->reduce($reducer) : null;
        };
        
        $result = Stream::from(self::VARIABLE_LENGTH_SEQUENCE)->join(['end'])
            ->remember($sequence)
            ->filter($nextSequenceDetector)
            ->remember($nextOperation)
            ->classify($sequence->value(0))
            ->call(static function () use ($sequence): void {
                $sequence->remove(0);
                $sequence->remove(-1);
            })
            ->map($mapper)
            ->call(static function () use ($sequence, $nextOperation): void {
                $sequence->clear();
                $sequence->write($nextOperation->value()->read(), $nextOperation->key()->read());
            })
            ->omit(null)
            ->categorize(Discriminators::byKey())
            ->toArrayAssoc();
        
        self::assertSame(self::VARIABLE_LENGTH_RESULT, $result);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_3(): void
    {
        $operation = Memo::value();
        $sequence = Memo::sequence();
        $results = [];
        
        $handler = static function () use ($operation, $sequence, &$results): void {
            switch ($operation->read()) {
                case '+': $reducer = static fn(int $acc, int $value): int => $acc + $value; break;
                case '-': $reducer = static fn(int $acc, int $value): int => $acc - $value; break;
                case '*': $reducer = static fn(int $acc, int $value): int => $acc * $value; break;
                default:
                    throw new \UnexpectedValueException('Unsupported operation: ' . $operation->read());
            }
            
            $results[$operation->read()][] = $sequence->reduce($reducer);
            $sequence->clear();
        };
        
        Stream::from(self::VARIABLE_LENGTH_SEQUENCE)->join(['end'])
            ->callWhen(static fn() => !$sequence->isEmpty(), $handler)
            ->only(['+', '-', '*', 'end'])
            ->remember($operation)
            ->readWhile('is_int')
            ->remember($sequence)
            ->run();
        
        self::assertSame(self::VARIABLE_LENGTH_RESULT, $results);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_and_handler_1(): void
    {
        $operation = Memo::value();
        $sequence = Memo::sequence();
        $results = [];
        
        $handler = static function () use ($operation, $sequence, &$results): void {
            switch ($operation->read()) {
                case '+': $reducer = static fn(int $acc, int $value): int => $acc + $value; break;
                case '-': $reducer = static fn(int $acc, int $value): int => $acc - $value; break;
                case '*': $reducer = static fn(int $acc, int $value): int => $acc * $value; break;
                default:
                    throw new \UnexpectedValueException('Unsupported operation: ' . $operation->read());
            }
            
            $results[$operation->read()][] = $sequence->reduce($reducer);
            $sequence->clear();
        };
        
        Stream::from(self::VARIABLE_LENGTH_SEQUENCE)
            ->only(['+', '-', '*'])
            ->remember($operation)
            ->readWhile('is_int', null, false, $handler)
            ->remember($sequence)
            ->run();
        
        self::assertSame(self::VARIABLE_LENGTH_RESULT, $results);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_and_handler_2(): void
    {
        $operation = Memo::value();
        $sequence = Memo::sequence();
        $results = [];
        
        $handler = static function () use ($operation, $sequence, &$results): void {
            switch ($operation->read()) {
                case '+': $reducer = static fn(int $acc, int $value): int => $acc + $value; break;
                case '-': $reducer = static fn(int $acc, int $value): int => $acc - $value; break;
                case '*': $reducer = static fn(int $acc, int $value): int => $acc * $value; break;
                default: $reducer = null;
            }
            
            if ($reducer !== null) {
                $results[$operation->read()][] = $sequence->reduce($reducer);
            }
            
            $sequence->clear();
        };
        
        Stream::from(self::VARIABLE_LENGTH_SEQUENCE)
            ->remember($operation)
            ->readWhile('is_int', null, false, $handler)
            ->remember($sequence)
            ->run();
        
        self::assertSame(self::VARIABLE_LENGTH_RESULT, $results);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_with_handler_and_dispatch(): void
    {
        $operation = Memo::value();
        $results = [];
        
        /* @var $reducers Reducer[] */
        $reducers = [
            '+' => Reducers::sum(),
            '-' => Reducers::generic(static fn(int $acc, int $value): int => $acc - $value),
            '*' => Reducers::generic(static fn(int $acc, int $value): int => $acc * $value),
        ];
        
        $handler = static function () use ($operation, $reducers, &$results): void {
            $reducer = $reducers[$operation->read()];
            $results[$operation->read()][] = $reducer->result();
            $reducer->reset();
        };
        
        Stream::from(self::VARIABLE_LENGTH_SEQUENCE)
            ->only(\array_keys($reducers))
            ->remember($operation)
            ->readWhile('is_int', null, false, $handler)
            ->dispatch($operation, $reducers)
            ->run();
 
        //then
        self::assertSame(self::VARIABLE_LENGTH_RESULT, $results);
    }
    
    public function test_process_sequences_of_variable_length_using_map_and_mapBy(): void
    {
        $operation = Memo::sequence(2);
        $sequence = Memo::sequence();
        
        $reset = static function () use ($operation, $sequence): void {
            $sequence->clear();
            $operation->remove(0);
        };
        
        $result = Stream::from(self::VARIABLE_LENGTH_SEQUENCE)->join(['end'])
            ->callWhen('is_string', $operation, $sequence)
            ->omit(Filters::OR('is_int', $sequence->inspect(new SequenceIsEmpty())))
            ->classify($operation->value(0))
            ->callWhen(Conditions::keyIs(':'), $reset)
            ->omit(':', Check::KEY)
            ->map($sequence)
            ->call($reset)
            ->mapBy(Discriminators::byKey(), [
                '+' => '\array_sum',
                '*' => '\array_product',
                '-' => Reducers::generic(static fn(int $acc, int $value): int => $acc - $value),
            ])
            ->categorize(Discriminators::byKey())
            ->toArrayAssoc();
        
        self::assertSame(self::VARIABLE_LENGTH_RESULT, $result);
    }
    
    public function test_mapBy_throws_exception_when_classifier_is_unknown(): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::mapperIsNotDefined('bar'));
        
        Stream::from([1])
            ->mapBy(
                static fn(): string => 'bar',
                ['foo' => 'strtoupper']
            )
            ->run();
    }
    
    public function test_mapBy(): void
    {
        $result = Stream::from(['aAa', 10, 'bBb', 6, 7, 'cCc', 12])
            ->mapBy('is_string', [
                true => 'strtolower',
                false => 'strval',
            ])
            ->toArray();
        
        self::assertSame(['aaa', '10', 'bbb', '6', '7', 'ccc', '12'], $result);
    }
    
    public function test_mapBy_onError_handler(): void
    {
        $result = Stream::from(['aAa', 'bBb', 'cCc'])
            ->onError(OnError::skip())
            ->mapBy(
                ['foo', 'zoo', 'bar'],
                [
                    'foo' => 'strtoupper',
                    'bar' => 'strtolower',
                ]
            )
            ->toArray();
        
        self::assertSame(['AAA', 'ccc'], $result);
    }
    
    public function test_find_sequence_in_stream_using_matches_and_transform_to_final_result(): void
    {
        $sequence = Memo::sequence(5);
        
        $result = Stream::from(['e', 't', 'q', 'e', 'j', 's', 'd', 't', 'u', 's', 'w', 'u', 's', 'f', 'd', 'e', 'f'])
            ->remember($sequence)
            ->find($sequence->matches(['u', 's', 'w', 'u', 's']))
            ->transform($sequence);
            
        self::assertTrue($result->found());
        self::assertSame([8 => 'u', 's', 'w', 'u', 's'], $result->get());
    }
    
    public function test_use_sequence_to_find_three_consequtive_elements_which_have_the_same_values_and_keys(): void
    {
        $sequence = Memo::sequence(3);
        
        $result = Stream::from(['a' => 'z', 'b' => 'x', 'c' => 'c', 'd' => 'd', 'e' => 'e', 'f' => 'n', 'g' => 'm'])
            ->remember($sequence)
            ->find($sequence->inspect(static function (SequenceMemo $sequence): bool {
                foreach ($sequence as $key => $value) {
                    if ($key !== $value) {
                        return false;
                    }
                }
                return true;
            }));
            
        self::assertTrue($result->found());
        self::assertSame(['c' => 'c', 'd' => 'd', 'e' => 'e'], $sequence->toArray());
    }
    
    public function test_sequences_of_particular_length_in_stream_1(): void
    {
        $length = Memo::value(-1);
        $sequence = Memo::sequence();
        $collector = Collectors::default();
        
        Stream::from(self::FIND_SEQUENCES_DATA)->join([0])
            ->callWhen(
                $sequence->inspect(new SequenceLengthIs($length)),
                static function () use ($sequence, $collector): void {
                    $collector->add($sequence->toArray());
                }
            )
            ->call(static function () use ($sequence): void {
                $sequence->clear();
            })
            ->remember($length)
            ->readWhile('is_string')
            ->remember($sequence)
            ->run();
            
        $result = $collector->stream()
            ->map(Mappers::reindexKeys())
            ->categorize('\count', true)
            ->toArrayAssoc();
        
        self::assertSame(self::FIND_SEQUENCES_RESULT, $result);
    }
    
    public function test_sequences_of_particular_length_in_stream_2(): void
    {
        $length = Memo::value(-1);
        $sequence = Memo::sequence();
        
        $collector = Stream::empty()
            ->map(Mappers::reindexKeys())
            ->categorize('\count', true)
            ->collect();
        
        Stream::from(self::FIND_SEQUENCES_DATA)->join([0])
            ->callWhen(
                $sequence->inspect(new SequenceLengthIs($length)),
                static function () use ($sequence, $collector): void {
                    $collector->consume([$sequence->toArray()]);
                }
            )
            ->call(static function () use ($sequence): void {
                $sequence->clear();
            })
            ->remember($length)
            ->readWhile('is_string')
            ->remember($sequence)
            ->run();
            
        self::assertSame(self::FIND_SEQUENCES_RESULT, $collector->toArrayAssoc());
    }
    
    public function test_sequences_of_particular_length_in_stream_3(): void
    {
        $length = Memo::value(-1);
        $sequence = Memo::sequence();
        
        $collector = Stream::empty()
            ->filter($sequence->inspect(new SequenceLengthIs($length)))
            ->map($sequence)
            ->map(Mappers::reindexKeys())
            ->categorize('\count', true)
            ->collect();
        
        Stream::from(self::FIND_SEQUENCES_DATA)->join([0])
            ->feed($collector)
            ->call(static function () use ($sequence): void {
                $sequence->clear();
            })
            ->remember($length)
            ->readWhile('is_string')
            ->remember($sequence)
            ->run();
            
        self::assertSame(self::FIND_SEQUENCES_RESULT, $collector->get());
    }
    
    public function test_sequences_of_particular_length_in_stream_4(): void
    {
        $length = Memo::value(-1);
        $sequence = Memo::sequence();

        $collector = Stream::empty()
            ->map($sequence)
            ->map('\array_values')
            ->categorize('\count', true)
            ->collect();

        $handler = static function () use ($length, $sequence, $collector): void {
            if ($sequence->count() === $length->read()) {
                $collector->consume([1]); //at least one element is required to trigger iteration in $collector
            }

            $sequence->clear();
        };

        Stream::from(self::FIND_SEQUENCES_DATA)
            ->remember($length)
            ->readWhile('is_string', null, false, $handler)
            ->remember($sequence)
            ->run();

        self::assertSame(self::FIND_SEQUENCES_RESULT, $collector->get());
    }
    
    public function test_use_negation_of_sequence_predicate_filter(): void
    {
        $sequence = Memo::sequence(3);
        $count = 0;
        
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f'])
            ->countIn($count)
            ->omit($sequence->inspect(new SequenceIsFull()))
            ->remember($sequence)
            ->filter($sequence->inspect(new SequenceIsFull()))
            ->map($sequence)
            ->toArrayAssoc();
        
        self::assertSame(6, $count);
        self::assertSame([2 => ['a', 'b', 'c']], $result);
    }
    
    public function test_LastOperation_can_consume_producer(): void
    {
        $stream = Stream::empty()
            ->onlyIntegers()
            ->greaterThan(5)
            ->lessThan(10)
            ->best(1, By::valueDesc())
            ->first();
        
        $stream->consume(['3', 2, 5, '7', 8, 2, '6', 9, 4]);
        $stream->consume(['g', 6, 2, 'b', 10, 'x']);
        $stream->consume([5, 'j', 7, 12, 'v', 4, 2, 'i']);
        
        self::assertSame(9, $stream->get());
    }
    
    public function test_stream_with_SequenceInspector_as_filter_negation(): void
    {
        $sequence = Memo::sequence(3);
        
        $result = Stream::from([1, 2, 3, 4, 5, 6, 7])
            ->remember($sequence)
            ->omit(Filters::NOT($sequence->inspect(new SequenceIsFull())))
            ->limit(3)
            ->toArray();
        
        self::assertSame([3, 4, 5], $result);
    }
    
    public function test_wrap_can_be_used_only_before_consume(): void
    {
        $stream = Stream::from(['j', 'd', 2, 'u', 9, 'd', 6])
            ->onlyIntegers()
            ->greaterThan(5)
            ->lessThan(10)
            ->reindex()
            ->sort()
            ->collect();
        
        //when wrap() is called on the stream first
        $result3 = $stream->wrap(['g', 6, 2, 'b', 10, 'x', 7])->toArray();
        $result4 = $stream->wrap([5, 'j', 7, 9, 'v', 4, 2, 'i'])->toArray();
        
        //then consume() can be called later
        $stream->consume(['3', 2, 5, '7', 8, 2, '6', 9, 4]);
        
        self::assertSame([7, 9], $result4);
        self::assertSame([6, 7], $result3);
        self::assertSame([6, 8, 9, 9], $stream->toArray());
    }
    
    public function test_exception_is_thrown_when_wrap_is_called_after_consume(): void
    {
        $this->expectExceptionObject(StreamExceptionFactory::cannotReuseUtilizedStream());
        
        $stream = Stream::empty()->collect();
        
        //because consume() is called first
        $stream->consume([1]);
        
        //an exception is thrown by wrap()
        $stream->wrap([1]);
    }
    
    public function test_skip_with_volatile_int(): void
    {
        $skip = Memo::value();
        
        $result = Stream::from([3, 'a', 'b', 'c', 6, 2, 8])
            ->callOnce($skip)
            ->skip(1)
            ->skip($skip)
            ->toArray();
        
        self::assertSame([6, 2, 8], $result);
    }
    
    public function test_skip_with_volatile_int_and_onerror_handler(): void
    {
        $skip = Memo::value();
        
        $result = Stream::from([3, 'a', 'b', 'c', 6, 2, 8])
            ->onError(OnError::skip())
            ->callOnce($skip)
            ->skip(1)
            ->skip($skip)
            ->toArray();
        
        self::assertSame([6, 2, 8], $result);
    }
    
    public function test_chunk_with_volatile_int_keep_keys(): void
    {
        $size = Memo::value();
        
        $result = Stream::from(self::VOLATILE_CHUNK_DATA)
            ->callWhen('is_int', $size)
            ->omit('is_int')
            ->chunk($size)
            ->sort(By::sizeDesc())
            ->categorize('\count', true)
            ->toArrayAssoc();
        
        self::assertSame(self::VOLATILE_CHUNK_KEEP_KEYS_RESULT, $result);
    }
    
    public function test_chunk_with_volatile_int_reindex_keys(): void
    {
        $size = Memo::value();
        
        $result = Stream::from(self::VOLATILE_CHUNK_DATA)
            ->callWhen('is_int', $size)
            ->omit('is_int')
            ->chunk($size, true)
            ->sort(By::sizeDesc())
            ->categorize('\count', true)
            ->toArrayAssoc();
        
        self::assertSame(self::VOLATILE_CHUNK_REINDEX_KEYS_RESULT, $result);
    }
    
    public function test_chunk_with_volatile_int_keep_keys_and_onerror_handler(): void
    {
        $size = Memo::value();
        
        $result = Stream::from(self::VOLATILE_CHUNK_DATA)
            ->onError(OnError::skip())
            ->callWhen('is_int', $size)
            ->omit('is_int')
            ->chunk($size)
            ->sort(By::sizeDesc())
            ->categorize('\count', true)
            ->toArrayAssoc();
        
        self::assertSame(self::VOLATILE_CHUNK_KEEP_KEYS_RESULT, $result);
    }
    
    public function test_chunk_with_volatile_int_reindex_keys_and_onerror_handler(): void
    {
        $size = Memo::value();
        
        $result = Stream::from(self::VOLATILE_CHUNK_DATA)
            ->onError(OnError::skip())
            ->callWhen('is_int', $size)
            ->omit('is_int')
            ->chunk($size, true)
            ->sort(By::sizeDesc())
            ->categorize('\count', true)
            ->toArrayAssoc();
        
        self::assertSame(self::VOLATILE_CHUNK_REINDEX_KEYS_RESULT, $result);
    }
    
    public function test_chunk_with_increasing_size(): void
    {
        $size = 1;
        
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o'])
            ->chunk(IntNum::readFrom($size))
            ->call(Consumers::changeIntBy($size, 1))
            ->concat('')
            ->toArray();
            
        self::assertSame(['a', 'bc', 'def', 'ghij', 'klmno'], $result);
    }
    
    public function test_chunk_with_changing_size(): void
    {
        $size = 1;
        
        $changeSize = Consumers::changeIntBy($size, static function () use (&$size): int {
            static $up = true;
            
            if ($up) {
                if ($size < 5) {
                    return 1;
                }
                
                $up = false;
            }
            
            return -1;
        });
        
        $result = Producers::repeater('*')
            ->stream()
            ->while(static function () use (&$size): bool {
                return $size > 0;
            })
            ->chunk(IntNum::readFrom($size), true)
            ->concat('')
            ->call($changeSize)
            ->toArray();
        
        self::assertSame(['*', '**', '***', '****', '*****', '****', '***', '**', '*'], $result);
    }
    
    public function test_stream_from_cyclic_producer(): void
    {
        $size = 1;
        
        $result = Producers::cyclic(['a', 'b', 'c', 'd'])
            ->stream()
            ->chunk(IntNum::readFrom($size))
            ->concat('')
            ->call(Consumers::changeIntBy($size, 1))
            ->limit(8)
            ->toArray();
        
        self::assertSame(['a', 'bc', 'dab', 'cdab', 'cdabc', 'dabcda', 'bcdabcd', 'abcdabcd'], $result);
    }
    
    public function test_cyclic_producer_can_return_keys_with_values(): void
    {
        $result = Producers::cyclic(['z' => 'q', 'x' => 'w', 'c' => 'e', 'v' => 'r'], true, 6)
            ->stream()
            ->makeTuple()
            ->concat('|')
            ->toArray();
        
        self::assertSame(['z|q', 'x|w', 'c|e', 'v|r', 'z|q', 'x|w'], $result);
    }
    
    public function test_cyclic_producer_can_return_reindexed_values(): void
    {
        $result = Producers::cyclic(['z' => 'q', 'x' => 'w', 'c' => 'e', 'v' => 'r'], false, 6)
            ->stream()
            ->map(static fn(string $v, int $k): string => $k.'|'.$v)
            ->toArray();
        
        self::assertSame(['0|q', '1|w', '2|e', '3|r', '4|q', '5|w'], $result);
    }
    
    public function test_empty_cyclic_producer_produces_empty_string(): void
    {
        self::assertSame('', Producers::cyclic([])->stream()->toString());
    }
    
    public function test_filter_by_reference_to_string_variable_1(): void
    {
        $current = '';
        
        $result = Producers::repeater('*')
            ->stream()
            ->scan('*', Reducers::concat())
            ->putIn($current)
            ->until(Filters::readFrom($current)->string()->is('*****'))
            ->toArray();
        
        self::assertSame(['*', '**', '***', '****'], $result);
    }
    
    public function test_filter_by_reference_to_external_variable_2(): void
    {
        $current = '';
        
        $result = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4])
            ->putIn($current)
            ->filter(Filters::readFrom($current)->type()->isString())
            ->toArray();
        
        self::assertSame(['a', 'b', 'c', 'd'], $result);
    }
    
    public function test_filter_by_reference_to_string_variable_3(): void
    {
        $current = '';
        $filter = Filters::readFrom($current)->string();
        
        $result = Stream::from(['fOo', 'bOon', 'doO', 'Boro', 'zOo', 'BoaR'])
            ->putIn($current)
            ->filter($filter->startsWith('bo')->ignoreCase())
            ->omit($filter->endsWith('ro')->ignoreCase())
            ->toArray();
        
        self::assertSame(['bOon', 'BoaR'], $result);
    }
    
    public function test_filter_by_reference_to_string_variable_4(): void
    {
        $current = '';
        $filter = Filters::readFrom($current)->string();
        
        $result = Stream::from(['boRo', 'fOo', 'bOon', 'doO', 'boro', 'zOo', 'BoaR'])
            ->putIn($current)
            ->filter($filter->startsWith('bo')->caseSensitive())
            ->omit($filter->endsWith('ro')->caseSensitive())
            ->toArray();
        
        self::assertSame(['boRo'], $result);
    }
    
    public function test_filter_by_IntVal_number(): void
    {
        $intVal = IntNum::infinitely([5, 6, 7, 8]);
        
        $result = Stream::from(['a', 1, 2, 'b', 'c', 3, 4, 'd'])
            ->filter(Filters::wrapIntValue($intVal)->isOdd())
            ->limit(4)
            ->toArray();
        
        self::assertSame(['a', 2, 'c', 4], $result);
    }
    
    public function test_filter_by_Memo_string_1(): void
    {
        $memo = Memo::value();
        
        $result = Stream::from(['aaba', 1, 2, 'boo', 3, 4, 'caba', 5, 6, 'doo', 7, 8])
            ->callWhen('\is_string', $memo)
            ->onlyIntegers()
            ->filter(Filters::wrapMemoReader($memo)->string()->endsWith('aba'))
            ->categorize($memo, true)
            ->toArrayAssoc();
        
        self::assertSame([
            'aaba' => [1, 2],
            'caba' => [5, 6],
        ], $result);
    }
    
    public function test_filter_by_Memo_string_2(): void
    {
        $memo = Memo::value();
        
        $result = Stream::from(['aaba', 1, 2, 'boo', 3, 4, 'caba', 5, 6, 'doo', 7, 8])
            ->callWhen('\is_string', $memo)
            ->onlyIntegers()
            ->filter(Filters::wrapMemoReader($memo)->string()->not()->endsWith('aba'))
            ->categorize($memo, true)
            ->toArrayAssoc();
        
        self::assertSame([
            'boo' => [3, 4],
            'doo' => [7, 8],
        ], $result);
    }
    
    public function test_filter_by_Memo_string_3(): void
    {
        $memo = Memo::value();
        
        $result = Stream::from(['aaba', 1, 2, 'boo', 3, 4, 'caba', 5, 6, 'doo', 7, 8])
            ->callWhen('\is_string', $memo)
            ->readMany(2, true)
            ->filter(Filters::wrapMemoReader($memo)->string()->notEndsWith('aba'))
            ->groupBy($memo)
            ->toArray();
        
        self::assertSame([
            'boo' => [3, 4],
            'doo' => [7, 8],
        ], $result);
    }
    
    public function test_filter_by_Memo_other(): void
    {
        $data = [
            '2021-08-16', 4, 7, 2,
            '2020-06-01', 3, 8, 8, 2, 3,
            '2020-07-02', 2, 12,
            '2019-05-19', 9, 2, 2, 1, 4, 5,
            '2020-05-30', 2, 6, 3, 4,
            '2021-01-02', 8, 16, 8,
        ];
        
        $date = Memo::value();
        
        $result = Stream::from($data)
            ->callWhen(Filters::isDateTime(), $date)
            ->filter(Filters::wrapMemoReader($date)->time()->between('2020-05-12', '2020-06-12'))
            ->onlyIntegers()
            ->categorize($date, true)
            ->map(Reducers::sum())
            ->toArrayAssoc();
        
        self::assertSame([
            '2020-06-01' => (3 + 8 + 8 + 2 + 3),
            '2020-05-30' => (2 + 6 + 3 + 4),
        ], $result);
    }
    
    public function test_consume_data_with_limit_set_on_stream(): void
    {
        $collector = Stream::empty()
            ->filter('is_string')
            ->limit(5)
            ->collect(true);
        
        $collector->consume([3, 'a', 4, 'b' , 'c']);
        $collector->consume([2, 'd', 'e', 6, 'f', 1]);
        $collector->consume([8, 'g', 3, 'h']);
    
        self::assertSame(['a', 'b', 'c', 'd', 'e'], $collector->toArray());
    }
    
    public function test_consume_with_chunk_and_sort(): void
    {
        $collector = Stream::empty()
            ->chunk(3)
            ->map(Reducers::sum())
            ->sort()
            ->collect(true);
        
        $collector->consume([3, 2, 6, 1, 7]);
        $collector->consume([2, 8, 3, 1, 2, 5, 6]);
        $collector->consume([8, 2, 1, 5, 3]);
        
        self::assertSame([8, 10, 11, 11, 12, 13], $collector->get());
    }
    
    public function test_consume_with_limit_chunk_and_sort(): void
    {
        $collector = Stream::empty()
            ->limit(14)
            ->chunk(3)
            ->map(Reducers::sum())
            ->sort()
            ->collect(true);
        
        $collector->consume([3, 2, 6, 1, 7]);
        $collector->consume([2, 8, 3, 1, 2, 5, 6]);
        $collector->consume([8, 2, 1, 5, 3]);
        
        self::assertSame([10, 10, 11, 12, 13], $collector->get());
    }
    
    public function test_consume_with_limit__tokenize_chunk_and_sort(): void
    {
        $collector = Stream::empty()
            ->tokenize()
            ->chunk(3)
            ->concat('')
            ->limit(7)
            ->rsort()
            ->collect(true);
        
        $collector->consume(['a b c d', 'e f', 'g h i']);
        $collector->consume(['j', 'k l m n']);
        $collector->consume(['o p q', 'r s', 't u v w', 'x y z']);
        
        self::assertSame(['stu', 'pqr', 'mno', 'jkl', 'ghi', 'def', 'abc'], $collector->get());
    }
    
    public function test_consume_with_onError_handler(): void
    {
        $sumator = Stream::empty()
            ->onError(OnError::skip())
            ->reduce(Reducers::sum());
        
        $sumator->consume([6, 3, 'foo', 2, 4]);
        
        self::assertSame(15, $sumator->get());
    }
    
    public function test_consume_with_assertion(): void
    {
        $this->expectExceptionObject(AssertionFailed::exception('foo', 2, Check::VALUE));
        
        Stream::empty()
            ->assert('is_int')
            ->reduce(Reducers::sum())
            ->consume([6, 3, 'foo', 2, 4]);
    }
    
    public function test_consume_with_error_thrown_by_substituted_producer(): void
    {
        $counter = Stream::empty()
            ->onError(OnError::skip())
            ->tokenize()
            ->mapWhen(Filters::onlyIn(['a', 'c']), Mappers::simple([3, 6]))
            ->filter(Filters::size()->eq(2))
            ->count();
        
        $counter->consume(['a b c', 'd a']);
        
        self::assertSame(3, $counter->get());
    }
    
    public function test_call_consume_directly_on_empty_stream(): void
    {
        $countAll = 0;
        $countStrings = 0;
        $strings = Collectors::values();
        
        $numbers = Stream::empty()
            ->onlyIntegers()
            ->fork(
                Discriminators::yesNo(Filters::greaterOrEqual(0), 'positive', 'negative'),
                Collectors::values()
            );
        
        $stream = Stream::empty()
            ->filter(Filters::isString()->or(Filters::isInt()))
            ->mapWhen('\is_string', '\strtolower')
            ->feed($numbers)
            ->countIn($countAll)
            ->onlyStrings()
            ->collectIn($strings)
            ->countIn($countStrings);
        
        $stream->consume([5, 'A', null, -3, 'B', false]);
        $stream->consume(['c', -4, false, 'D', 2]);
        $stream->consume(['d', -1, true, '3', 0]);
        
        self::assertSame(12, $countAll);
        self::assertSame(6, $countStrings);
        
        self::assertSame([
            'positive' => [5, 2, 0],
            'negative' => [-3, -4, -1],
        ], $numbers->toArrayAssoc());
        
        $stream->consume(['a', 1, 'b', 2]);
        
        self::assertSame(16, $countAll);
        self::assertSame(8, $countStrings);
        
        self::assertSame('a,b,c,d,d,3,a,b', $strings->toString());
        self::assertSame('', $stream->toString());
    }
    
    public function test_insert_data_into_stream_using_consume(): void
    {
        $stream = Stream::from([1, 2, 3, 4, 5, 6, 7]);
        $filter = Filters::number();
        
        $stream->callWhen($filter->isInt()->and($filter->isEven()), static function () use ($stream) {
            static $data = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'];
            $stream->consume(\array_splice($data, 0, 3));
        });
        
        self::assertSame([1, 'a', 'b', 'c', 2, 3, 'd', 'e', 'f', 4, 5, 'g', 'h', 'i', 6, 7], $stream->toArray());
    }
    
    public function test_clone_the_last_operation_is_prohibited(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to protected');
        
        $operation = Stream::from([1, 2, 3])->reduce(Reducers::sum());
        
        /** @noinspection PhpExpressionResultUnusedInspection */
        $this->cloneLastOperation($operation);
    }
    
    private function cloneLastOperation($operation): void
    {
        /** @noinspection PhpExpressionResultUnusedInspection */
        clone $operation;
    }
    
    public function test_filter_value_first(): void
    {
        self::assertSame('a', Stream::from([1, 'a', 2, 'b'])->filter('is_string')->first()->get());
    }
    
    public function test_filter_key_first(): void
    {
        self::assertSame('a', Stream::from([1, 'a', 2, 'b'])->flip()->filter('is_string', Check::KEY)->first()->key());
    }
    
    public function test_filter_both_first(): void
    {
        $result = Stream::from([1 => 'a', 'a' => 1, 'b' => 'c', 'd' => 'd'])
            ->filter('is_string', Check::BOTH)
            ->first()
            ->toArrayAssoc();
        
        self::assertSame(['b' => 'c'], $result);
    }
    
    public function test_filter_any_first(): void
    {
        $result = Stream::from([1 => 2, 'a' => 1, 3 => 'c', 'd' => 'd'])
            ->filter('is_string', Check::ANY)
            ->first()
            ->toArrayAssoc();
        
        self::assertSame(['a' => 1], $result);
    }
    
    public function test_omit_value_first(): void
    {
        self::assertSame('a', Stream::from([1, 'a', 2, 'b'])->omit('is_int')->first()->get());
    }
    
    public function test_omit_key_first(): void
    {
        self::assertSame('a', Stream::from([1, 'a', 2, 'b'])->flip()->omit('is_int', Check::KEY)->first()->key());
    }
    
    public function test_omit_both_first(): void
    {
        $result = Stream::from(['b' => 'c', 1 => 'a', 'a' => 1, 'd' => 'd'])
            ->omit('is_string', Check::BOTH)
            ->first()
            ->toArrayAssoc();
        
        self::assertSame([1 => 'a'], $result);
    }
    
    public function test_omit_any_first(): void
    {
        $result = Stream::from(['a' => 1, 3 => 'c', 'd' => 'd', 1 => 2, 'e' => 'f'])
            ->omit('is_string', Check::ANY)
            ->first()
            ->toArrayAssoc();
        
        self::assertSame([1 => 2], $result);
    }
    
    public function test_last_operation_api(): void
    {
        $reducer = Stream::from([1, 2, 3, 4, 5])->reduce(Reducers::sum());
        
        self::assertSame(6, $reducer->wrap([1, 2, 3])->get());
        
        self::assertSame(0, $reducer->key());
        self::assertSame(15, $reducer->get());
        
        self::assertSame([15], $reducer->toArray());
        self::assertSame([15], $reducer->toArrayAssoc());
        
        self::assertSame('15', $reducer->toString());
        
        self::assertSame('15', $reducer->toJson());
        self::assertSame('[15]', $reducer->toJsonAssoc());
        
        self::assertSame(1, $reducer->count());
        
        self::assertTrue($reducer->found());
        self::assertFalse($reducer->notFound());
        self::assertSame(15, $reducer->getOrElse(6));
    }
    
    public function test_use_dispatch_to_send_data_to_sequence_memo_with_onerror_handler(): void
    {
        $sequence = Memo::sequence(3);
        $sumator = Reducers::sum();
        
        Stream::from([1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e', 6, 'f'])
            ->onError(OnError::skip())
            ->dispatch('is_string', [
                true => $sequence,
                false => $sumator,
            ])
            ->run();
        
        self::assertSame(21, $sumator->result());
        self::assertSame(['d', 'e', 'f'], $sequence->getValues());
    }
    
    public function test_use_fork_to_send_data_sequence_memo(): void
    {
        $result = Stream::from(['aa', 'vvv', 'c', 'dddd', 'aa', 'c', 'vvv', 'aa'])
            ->fork('strlen', Memo::sequence())
            ->toArrayAssoc();
        
        self::assertSame([
            2 => ['aa', 4 => 'aa', 7 => 'aa'],
            3 => [1 => 'vvv', 6 => 'vvv'],
            1 => [2 => 'c', 5 => 'c'],
            4 => [3 => 'dddd'],
        ], $result);
    }
    
    public function test_use_fork_to_send_data_sequence_memo_with_onerror_handler(): void
    {
        $result = Stream::from(['aa', 'vvv', 'c', 'dddd', 'aa', 'c', 'vvv', 'aa'])
            ->onError(OnError::abort())
            ->fork('strlen', Memo::sequence())
            ->toArrayAssoc();
        
        self::assertSame([
            2 => ['aa', 4 => 'aa', 7 => 'aa'],
            3 => [1 => 'vvv', 6 => 'vvv'],
            1 => [2 => 'c', 5 => 'c'],
            4 => [3 => 'dddd'],
        ], $result);
    }
    
    public function test_filter_and_map_by_arguments_of_array_send_to_callable(): void
    {
        $collector = Collectors::default();
        
        $result = Stream::from(self::ROWSET)
            ->reorder(['name', 'age', 'sex', 'id'])
            ->callArgs(static function (string $name, int $age, $_, int $id) use ($collector) {
                if ($age === 18) {
                    $collector->set($id, $name);
                }
            })
            ->filterArgs(static fn($_, int $age, string $sex): bool => $sex === 'female' && $age < 18)
            ->mapArgs(static fn(string $name, int $age): string => $name.' ('.$age.')')
            ->toArray();
        
        self::assertSame(['Sue (17)', 'Joanna (15)'], $result);
        self::assertSame([9 => 'Chris', 1 => 'Kila'], $collector->toArray());
    }
    
    public function test_filter_and_map_by_arguments_of_array_send_to_callable_with_onerror_handler(): void
    {
        $collector = Collectors::default();
        
        $result = Stream::from(self::ROWSET)
            ->onError(OnError::abort())
            ->reorder(['name', 'age', 'sex', 'id'])
            ->callArgs(static function (string $name, int $age, $_, int $id) use ($collector) {
                if ($age === 18) {
                    $collector->set($id, $name);
                }
            })
            ->filterArgs(static fn($_, int $age, string $sex): bool => $sex === 'female' && $age < 18)
            ->mapArgs(static fn(string $name, int $age): string => $name.' ('.$age.')')
            ->toArray();
        
        self::assertSame(['Sue (17)', 'Joanna (15)'], $result);
        self::assertSame([9 => 'Chris', 1 => 'Kila'], $collector->toArray());
    }
    
}