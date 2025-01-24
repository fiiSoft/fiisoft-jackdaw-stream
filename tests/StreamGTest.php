<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class StreamGTest extends TestCase
{
    public function test_process_sequences_of_constant_length_using_chunks(): void
    {
        //given
        $data = [
            '+', 5, 2, '-', 8, 3,
            ':', 9, 3, //omit
            '+', 2, 4, '*', 3, 6, '-', 5, 2
        ];
        
        //when
        $result = Stream::from($data)
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
        
        //then
        self::assertSame([
            '+' => [7, 6],
            '-' => [5, 3],
            '*' => [18],
        ], $result);
    }
    
    public function test_process_sequences_of_constant_length_using_memo(): void
    {
        //given
        $data = [
            '+', 5, 2, '-', 8, 3,
            ':', 9, 3, //omit
            '+', 2, 4, '*', 3, 6, '-', 5, 2
        ];
        
        //when
        $seq = Memo::sequence(3);
        
        $result = Stream::from($data)
            ->remember($seq)
            ->filter(static fn(): bool => $seq->isFull())
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
        
        //then
        self::assertSame([
            '+' => [7, 6],
            '-' => [5, 3],
            '*' => [18],
        ], $result);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_1(): void
    {
        //given
        $data = [
            '+', 5, 2, 4, '-', 8, 3, '+', 2, 4, '*', 3, 6, 2,
            ':', 18, 2, //omit
            '-', 5, 2, 2, '+', 9, 3, 5,
            ':', 24, 3, 2, //omit
            '-', 15, 8, 3, 5
        ];
        
        //when
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
        
        $result = Stream::from($data)->join(['stop'])
            ->remember($sequence)
            ->filter($nextSequenceDetector)
            ->classify($sequence->value(0))
            ->map($handler)
            ->omit(null)
            ->categorize(Discriminators::byKey())
            ->toArrayAssoc();
        
        //then
        self::assertSame([
            '+' => [11, 6, 17],
            '-' => [5, 1, -1],
            '*' => [36],
        ], $result);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_2(): void
    {
        //given
        $data = [
            '+', 5, 2, 4, '-', 8, 3, '+', 2, 4, '*', 3, 6, 2,
            ':', 18, 2, //omit
            '-', 5, 2, 2, '+', 9, 3, 5,
            ':', 24, 3, 2, //omit
            '-', 15, 8, 3, 5
        ];
        
        //when
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
        
        $result = Stream::from($data)->join(['stop'])
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
        
        //then
        self::assertSame([
            '+' => [11, 6, 17],
            '-' => [5, 1, -1],
            '*' => [36],
        ], $result);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_3(): void
    {
        //given
        $data = [
            '+', 5, 2, 4, '-', 8, 3, '+', 2, 4, '*', 3, 6, 2,
            ':', 18, 2, //omit
            '-', 5, 2, 2, '+', 9, 3, 5,
            ':', 24, 3, 2, //omit
            '-', 15, 8, 3, 5
        ];
        
        //when
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
        
        Stream::from($data)->join(['stop'])
            ->callWhen(static fn() => !$sequence->isEmpty(), $handler)
            ->only(['+', '-', '*', 'stop'])
            ->remember($operation)
            ->readWhile('is_int')
            ->remember($sequence)
            ->run();
        
        //then
        self::assertSame([
            '+' => [11, 6, 17],
            '-' => [5, 1, -1],
            '*' => [36],
        ], $results);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_and_handler_1(): void
    {
        //given
        $data = [
            '+', 5, 2, 4, '-', 8, 3, '+', 2, 4, '*', 3, 6, 2,
            ':', 18, 2, //omit
            '-', 5, 2, 2, '+', 9, 3, 5,
            ':', 24, 3, 2, //omit
            '-', 15, 8, 3, 5
        ];
        
        //when
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
        
        Stream::from($data)
            ->only(['+', '-', '*'])
            ->remember($operation)
            ->readWhile('is_int', null, false, $handler)
            ->remember($sequence)
            ->run();
        
        //then
        self::assertSame([
            '+' => [11, 6, 17],
            '-' => [5, 1, -1],
            '*' => [36],
        ], $results);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_and_handler_2(): void
    {
        //given
        $data = [
            '+', 5, 2, 4, '-', 8, 3, '+', 2, 4, '*', 3, 6, 2,
            ':', 18, 2, //omit
            '-', 5, 2, 2, '+', 9, 3, 5,
            ':', 24, 3, 2, //omit
            '-', 15, 8, 3, 5
        ];
        
        //when
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
        
        Stream::from($data)
            ->remember($operation)
            ->readWhile('is_int', null, false, $handler)
            ->remember($sequence)
            ->run();
        
        //then
        self::assertSame([
            '+' => [11, 6, 17],
            '-' => [5, 1, -1],
            '*' => [36],
        ], $results);
    }
    
    public function test_process_sequences_of_variable_length_using_memo_with_handler_and_dispatch(): void
    {
        //given
        $data = [
            '+', 5, 2, 4, '-', 8, 3, '+', 2, 4, '*', 3, 6, 2,
            ':', 18, 2, //omit
            '-', 5, 2, 2, '+', 9, 3, 5,
            ':', 24, 3, 2, //omit
            '-', 15, 8, 3, 5
        ];
        
        //when
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
        
        Stream::from($data)
            ->only(\array_keys($reducers))
            ->remember($operation)
            ->readWhile('is_int', null, false, $handler)
            ->dispatch($operation, $reducers)
            ->run();
 
        //then
        self::assertSame([
            '+' => [11, 6, 17],
            '-' => [5, 1, -1],
            '*' => [36],
        ], $results);
    }
}