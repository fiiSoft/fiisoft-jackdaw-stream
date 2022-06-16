<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Internal\CircularBufferIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Producer\Resource\PDOStatementAdapter;
use FiiSoft\Jackdaw\Producer\Resource\TextFileReader;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class ProducersTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_wrong_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::getAdapter('wrong_argument');
    }
    
    public function test_RandomInt_generator(): void
    {
        $producer = Producers::randomInt(1, 500, 10);
        $count = 0;
        
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            self::assertIsInt($item->value);
            self::assertTrue($item->value >= 1);
            self::assertTrue($item->value <= 500);
            ++$count;
        }
        
        self::assertSame(10, $count);
    }
    
    public function test_SequentialInt_generator(): void
    {
        $producer = Producers::sequentialInt(1, 2, 5);
        $buffer = [];
        
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            $buffer[] = $item->value;
        }
    
        self::assertSame([1,3,5,7,9], $buffer);
    }
    
    public function test_RandomString_geneartor(): void
    {
        $producer = Producers::randomString(3, 10, 5);
        $count = 0;
    
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            self::assertIsString($item->value);
            self::assertTrue(\strlen($item->value) >= 3);
            self::assertTrue(\strlen($item->value) <= 10, 'length is '.\strlen($item->value));
            ++$count;
        }
    
        self::assertSame(5, $count);
    }
    
    public function test_RandomUuid_generator(): void
    {
        if (!\class_exists('\Ramsey\Uuid\Uuid')) {
            self::markTestSkipped('Class Ramsey\Uuid\Uuid is required to run this test');
        }
        
        $producer = Producers::randomUuid(true, 5);
        $count = 0;
    
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            self::assertIsString($item->value);
            self::assertSame(32, \strlen($item->value));
            self::assertStringMatchesFormat('%x', $item->value);
            ++$count;
        }
        
        self::assertSame(5, $count);
    }
    
    public function test_SequentialInt_generator_throws_exception_on_param_step_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::sequentialInt(1, 0, 10);
    }
    
    public function test_SequentialInt_generator_throws_exception_on_invalid_param_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::sequentialInt(1, 1, -1);
    }
    
    public function test_RandomString_throws_exception_on_invalid_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomString(1, 10, -1);
    }
    
    public function test_RandomString_throws_exception_when_maxLength_is_less_than_minLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomString(11, 10, 1);
    }
    
    public function test_RandomString_can_generate_string_of_const_length(): void
    {
        $producer = Producers::randomString(5, 5, 3);
        $item = new Item();
    
        foreach ($producer->feed($item) as $_) {
            self::assertSame(5, \strlen($item->value));
        }
    }
    
    public function test_RandomInt_throws_exception_on_invalid_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomInt(1, 2, -1);
    }
    
    public function test_RandomInt_thows_exception_when_max_is_not_greater_than_min(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Producers::randomInt(2, 2);
    }
    
    public function test_Collatz_generator_with_known_initial_value_gives_predicable_series_of_numbers(): void
    {
        $producer = Producers::collatz(3);
        $buffer = [];
    
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            $buffer[] = $item->value;
        }
    
        self::assertSame([3, 10, 5, 16, 8, 4, 2, 1], $buffer);
    }
    
    public function test_Collatz_generator_with_random_initial_value(): void
    {
        $producer = Producers::collatz();
        $buffer = [];
    
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            $buffer[] = $item->value;
        }
    
        $expected = [16, 8, 4, 2, 1];
    
        if (\count($buffer) < \count($expected)) {
            $expected = \array_slice($expected, -\count($buffer));
        }
        
        self::assertSame($expected, \array_slice($buffer, -\count($expected)));
    }
    
    public function test_Collatz_generator_throws_exception_when_initial_number_is_below_one(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param startNumber');
        
        Producers::collatz(0);
    }
    
    public function test_RandomUuid_generator_throws_exception_when_limit_is_less_than_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param limit');
        
        Producers::randomUuid(true, -1);
    }
    
    public function test_PDOStatement_generator(): void
    {
        $stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $stmt->expects(self::exactly(2))->method('fetch')->willReturnOnConsecutiveCalls(
            ['id' => 5, 'name' => 'John'],
            false,
        );
        
        $producer = Producers::getAdapter($stmt);
        self::assertInstanceOf(PDOStatementAdapter::class, $producer);
        
        $item = new Item();
        $buffer = [];
    
        foreach ($producer->feed($item) as $_) {
            $buffer[] = $item->value;
        }
        
        self::assertSame([['id' => 5, 'name' => 'John']], $buffer);
    }
    
    public function test_create_producer_with_some_object_as_element_of_array(): void
    {
        //given
        $object = new class {
            public string $field = 'foo';
        };
        
        $item = new Item();
        $producer = Producers::from([$object]);
        
        //when
        foreach ($producer->feed($item) as $_) {
            //then
            self::assertIsObject($item->value);
            
            if (isset($item->value->field)) {
                self::assertSame('foo', $item->value->field);
            } else {
                self::fail('Property field is not set in value object');
            }
        }
    }
    
    public function test_TextFileReader_throws_exception_when_param_is_not_resource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param resource');
        
        new TextFileReader('this is not file pointer');
    }
    
    public function test_can_read_lines_from_any_readable_resource(): void
    {
        $fp = \fopen('php://memory', 'rwb');
        \fwrite($fp, 'foo'."\n".'bar'."\n");
        \rewind($fp);
        
        $producer = Producers::getAdapter($fp);
        $item = new Item();
        $buffer = [];
    
        foreach ($producer->feed($item) as $_) {
            $buffer[] = \trim($item->value);
        }
        
        \fclose($fp);
        self::assertSame(['foo', 'bar'], $buffer);
    }
    
    public function test_can_close_producer_on_read_finish(): void
    {
        $fp = \fopen('php://memory', 'rwb');
        $producer = Producers::resource($fp, true);
    
        $item = new Item();
        foreach ($producer->feed($item) as $_) {
            //just iterate
        }
        
        self::assertIsClosedResource($fp);
    }
    
    public function test_resource_reader_throws_exception_when_param_readBytes_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param readBytes');
    
        Producers::resource(\fopen('php://memory', 'rwb'), true, 0);
    }
    
    public function test_Tokenizer_can_be_reused(): void
    {
        $tokenizer = Producers::tokenizer(' ');
        
        $tokenizer->restartWith('This is first string. It uses spaces, dots and commas.');
        $first = Stream::from($tokenizer)->map('strtolower')->toString(' ');
        self::assertSame('this is first string. it uses spaces, dots and commas.', $first);
        
        $tokenizer->restartWith('This is second string. It uses spaces, dots and commas.', ' .,');
        $second = Stream::from($tokenizer)->map('strtolower')->toString(' ');
        self::assertSame('this is second string it uses spaces dots and commas', $second);
    }
    
    public function test_Flattener_throws_exception_when_try_to_increase_level_with_negative_number(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param level, must be greater than 0');
    
        Producers::flattener()->increaseLevel(-1);
    }
    
    public function test_CircularBufferIterator_throws_exception_when_param_buffer_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param buffer');
        
        new CircularBufferIterator('wrong buffer', 3, 3);
    }
    
    public function test_CircularBufferIterator_throws_exception_when_param_count_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param count');
        
        new CircularBufferIterator([], -1, 3);
    }
    
    public function test_CircularBufferIterator_throws_exception_when_param_index_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param index');
        
        new CircularBufferIterator([], 5, 6);
    }
    
    public function test_Flattener_prevents_decrease_level_when_it_is_0(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot decrease level');
        
        Producers::flattener([], 0)->decreaseLevel();
    }
    
    public function test_Flattener_prevents_decrease_level_when_it_is_1(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot decrease level');
        
        Producers::flattener([], 1)->decreaseLevel();
    }
    
    public function test_Producer_can_create_Stream_to_iterate_over_values_produced_by_itself(): void
    {
        self::assertSame('A,B,C', Producers::getAdapter(['a', 'b', 'c'])->stream()->map('\strtoupper')->toString());
    }
    
    public function test_Queue_producer(): void
    {
        $item = new Item();
        
        $producer = Producers::queue();
        $producer->appendMany(['a', 'b', 'c']); //0:a,1:b,2:c
        
        $generator = $producer->feed($item);
        $generator->valid(); //1:b,2:c
    
        self::assertSame(0, $item->key);
        self::assertSame('a', $item->value);
    
        $generator->next(); //2:c
        self::assertSame(1, $item->key);
        self::assertSame('b', $item->value);
        
        $producer->append('d'); //2:c,n:d
    
        $generator->next(); //n:d
        self::assertSame(2, $item->key);
        self::assertSame('c', $item->value);
        
        $producer->append('e'); //n:d,n:e
    
        $generator->next(); //n:e
        self::assertSame(0, $item->key);
        self::assertSame('d', $item->value);
    
        $producer->prependMany(['f', 'g']); //0:f,1:g,n:e
        
        $generator->next(); //1:g,n:e
        self::assertSame(0, $item->key);
        self::assertSame('f', $item->value);
        
        $producer->prependMany(['h', 'i'], true); //1:i,0:h,1:g,n:e
        
        $generator->next(); //0:h,1:g,n:e
        self::assertSame(1, $item->key);
        self::assertSame('i', $item->value);
        
        $producer->appendMany(['j', 'k']); //0:h,1:g,n:e,0:j,1:k
        
        $generator->next(); //1:g,n:e,0:j,1:k
        self::assertSame(0, $item->key);
        self::assertSame('h', $item->value);
        
        $generator->next(); //n:e,0:j,1:k
        self::assertSame(1, $item->key);
        self::assertSame('g', $item->value);
        
        $generator->next(); //0:j,1:k
        self::assertSame(1, $item->key);
        self::assertSame('e', $item->value);
        
        $generator->next(); //1:k
        self::assertSame(0, $item->key);
        self::assertSame('j', $item->value);
        
        $generator->next(); //empty
        self::assertSame(1, $item->key);
        self::assertSame('k', $item->value);
    }
}