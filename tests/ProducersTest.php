<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Generator\CombinedArrays;
use FiiSoft\Jackdaw\Producer\Generator\CombinedGeneral;
use FiiSoft\Jackdaw\Producer\Internal\BucketListIterator;
use FiiSoft\Jackdaw\Producer\Internal\CircularBufferIterator;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\PushProducer;
use FiiSoft\Jackdaw\Producer\Internal\ReverseArrayIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseNumericalArrayIterator;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Producer\Resource\PDOStatementAdapter;
use FiiSoft\Jackdaw\Producer\Resource\TextFileReader;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;
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
    
    public function test_RandomString_generator(): void
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
        $second = Stream::from($tokenizer)->map('ucfirst')->toString(' ');
        self::assertSame('This Is Second String It Uses Spaces Dots And Commas', $second);
    }
    
    public function test_Tokenizer_produces_the_same_results_each_time(): void
    {
        $tokenizer = Producers::tokenizer(' .,', 'This is second string. It uses spaces, dots and commas.');
        
        $expected = ['This','Is','Second','String','It','Uses','Spaces','Dots','And','Commas',];
        
        self::assertSame($expected, $tokenizer->stream()->map('ucfirst')->toArrayAssoc());
        self::assertSame($expected, $tokenizer->stream()->map('ucfirst')->toArrayAssoc());
        self::assertSame($expected, Stream::from($tokenizer)->map('ucfirst')->toArrayAssoc());
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
    
    public function test_reusable_Producer_can_crate_Stream_multiple_times(): void
    {
        $producer = Producers::fromArray(['a', 1, 'b', 2, 'c', 3]);
        
        self::assertSame([1, 2, 3], $producer->stream()->filter('\is_int')->toArray());
        self::assertSame(['a', 'b', 'c'], $producer->stream()->filter('\is_string')->toArray());
    }
    
    public function test_QueueProducer_details(): void
    {
        $item = new Item();
        
        $producer = Producers::queue();
        $producer->appendMany(['a', 'b', 'c']); //0:a,1:b,2:c
        self::assertFalse($producer->isEmpty());
        
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
        
        self::assertTrue($producer->isEmpty());
    }
    
    public function test_MultiProducer(): void
    {
        $producer = Producers::multiSourced(
            ['a', 'b'],
            Producers::sequentialInt(1, 1, 2)
        );
        
        self::assertFalse($producer->isEmpty());
        self::assertSame(['a', 'b', 1, 2], $producer->stream()->toArray());
        self::assertFalse($producer->isEmpty());
    }
    
    public function test_MultiProducer_can_be_empty_only_when_all_producers_within_it_are_empty(): void
    {
        $producer = Producers::multiSourced(
            [],
            Producers::sequentialInt(1, 1, 0)
        );
        
        self::assertTrue($producer->isEmpty());
    }
    
    public function test_CircularBufferIterator_can_tell_if_is_empty_only_in_some_circumstances(): void
    {
        //it can tell correctly if is empty for arrays...
        $cbi = new CircularBufferIterator([], 5, 0);
        self::assertTrue($cbi->isEmpty());
        
        $cbi = new CircularBufferIterator(['a'], 5, 0);
        self::assertFalse($cbi->isEmpty());
        
        //and \Countable objects
        $cbi = new CircularBufferIterator(new \ArrayObject(), 5, 0);
        self::assertTrue($cbi->isEmpty());
        
        $cbi = new CircularBufferIterator(new \ArrayObject(['a']), 5, 0);
        self::assertFalse($cbi->isEmpty());
        
        //but for others, isEmpty() always returns FALSE!
        $buffer = new \SplFixedArray(5);
        $cbi = new CircularBufferIterator($buffer, 5, 0);
        self::assertFalse($cbi->isEmpty());
        
        $buffer = new \SplFixedArray(5);
        $buffer[0] = 'a';
        $cbi = new CircularBufferIterator($buffer, 5, 0);
        self::assertFalse($cbi->isEmpty());
    }
    
    public function test_CircularBufferIterator_can_return_the_last_element(): void
    {
        $cbi = new CircularBufferIterator([], 0, 0);
        self::assertNull($cbi->getLast());
        
        $cbi = new CircularBufferIterator([new Item(2, 'a')], 1, 0);
        self::assertEquals(new Item(2, 'a'), $cbi->getLast());
        
        $cbi = new CircularBufferIterator([new Item(2, 'a'), new Item(1, 'b')], 2, 0);
        self::assertEquals(new Item(1, 'b'), $cbi->getLast());
        
        $cbi = new CircularBufferIterator([new Item(2, 'a'), new Item(1, 'b')], 2, 1);
        self::assertEquals(new Item(2, 'a'), $cbi->getLast());
        
        $cbi = new CircularBufferIterator([new Item(2, 'a'), new Item(1, 'b')], 2, 2);
        self::assertEquals(new Item(1, 'b'), $cbi->getLast());
    }
    
    public function test_ReverseItemsIterator_can_tell_if_is_empty(): void
    {
        $cbi = new ReverseItemsIterator([]);
        self::assertTrue($cbi->isEmpty());
        
        $cbi = new ReverseItemsIterator([new Item(0, 1)]);
        self::assertFalse($cbi->isEmpty());
    }
    
    public function test_QueueProducer_can_return_the_last_element(): void
    {
        $producer = Producers::queue();
        self::assertNull($producer->getLast());
        
        $producer->appendMany(['a', 'v', 'c']);
        self::assertEquals(new Item(2, 'c'), $producer->getLast());
    }
    
    public function test_MultiProducer_can_merge_other_MultiProducer(): void
    {
        $first = Producers::multiSourced(['a', 'b'], new \ArrayIterator([1, 2]));
        $second = Producers::multiSourced($first, new \ArrayObject(['foo', 'bar']));
        
        self::assertSame(['a', 'b', 1, 2, 'foo', 'bar'], $second->stream()->toArray());
    }
    
    public function test_MultiProducer_can_tell_if_is_countable_or_not(): void
    {
        $nonCountable = Producers::multiSourced([1, 2], Producers::collatz());
        self::assertFalse($nonCountable->isCountable());
        
        $countable = Producers::multiSourced(['a', 'b'], new \ArrayIterator([1,2,3]));
        self::assertTrue($countable->isCountable());
    }
    
    public function test_MultiProducer_can_return_number_of_elements_when_is_countable_only(): void
    {
        $countable = Producers::multiSourced(['a', 'b'], new \ArrayIterator([1,2,3]));
        
        self::assertTrue($countable->isCountable());
        self::assertSame(5, $countable->count());
        
        $nonCountable = Producers::multiSourced([1, 2], Producers::collatz());
        self::assertFalse($nonCountable->isCountable());
        
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('MultiProducer cannot count how many elements can produce!');
        
        $nonCountable->count();
    }
    
    public function test_MultiProducer_can_return_the_last_element_when_its_possible(): void
    {
        $emptyProducer = Producers::multiSourced();
        self::assertNull($emptyProducer->getLast());
        
        $lastAvailable = Producers::multiSourced(['a', 'b'], new \ArrayIterator([1,2,3]));
        self::assertEquals(new Item(2, 3), $lastAvailable->getLast());
        
        $lastNotAvailable = Producers::multiSourced(new \ArrayIterator([1,2,3]), Producers::randomInt());
        self::assertNull($lastNotAvailable->getLast());
    }
    
    public function test_ArrayIteratorAdapter_not_empty(): void
    {
        $producer = Producers::getAdapter(new \ArrayIterator(['a', 'b', 'c']));
        
        self::assertFalse($producer->isEmpty());
        self::assertTrue($producer->isCountable());
        self::assertSame(3, $producer->count());
        self::assertEquals(new Item(2, 'c'), $producer->getLast());
    }
    
    public function test_ArrayIteratorAdapter_empty(): void
    {
        $producer = Producers::getAdapter(new \ArrayIterator());
        
        self::assertTrue($producer->isEmpty());
        self::assertTrue($producer->isCountable());
        self::assertSame(0, $producer->count());
        self::assertNull($producer->getLast());
    }
    
    public function test_IteratorAdaptor_can_count_number_of_elements_only_when_iterator_is_countable(): void
    {
        $countable = Producers::getAdapter(new \ArrayIterator(['a', 'b', 'c']));
        
        self::assertTrue($countable->isCountable());
        self::assertSame(3, $countable->count());
        
        $notCountable = Producers::getAdapter(new class implements \IteratorAggregate {
            public function getIterator(): \ArrayIterator {
                return new \ArrayIterator(['a', 'b', 'c']);
            }
        });
        
        self::assertFalse($notCountable->isCountable());
        
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot count elements in non-countable Traversable iterator');
        
        $notCountable->count();
    }
    
    public function test_IteratorAdapter_never_returns_the_last_element(): void
    {
        $producer = Producers::getAdapter(new \ArrayObject(['a', 'b']));
        
        self::assertFalse($producer->isEmpty());
        self::assertTrue($producer->isCountable());
        self::assertSame(2, $producer->count());
        self::assertNull($producer->getLast());
    }
    
    public function test_ArrayAdapter_cen_return_the_last_element(): void
    {
        self::assertNull(Producers::getAdapter([])->getLast());
        
        self::assertEquals(new Item(1, 'b'), Producers::getAdapter(['a', 'b'])->getLast());
    }
    
    public function test_RandomString_producer_never_returns_the_last_value(): void
    {
        self::assertNull(Producers::randomString(15)->getLast());
    }
    
    public function test_RandomUuid_producer_never_returns_the_last_value(): void
    {
        self::assertNull(Producers::randomUuid()->getLast());
    }
    
    public function test_SequentialInt_producer_can_compute_value_of_last_element(): void
    {
        $producer = Producers::sequentialInt(5, 3, 4);
        
        self::assertFalse($producer->isEmpty());
        self::assertTrue($producer->isCountable());
        self::assertSame(4, $producer->count());
        
        $last = $producer->getLast();
        self::assertEquals(new Item(3, 14), $last);
        
        $generated = $producer->stream()->toArrayAssoc();
        self::assertSame([5, 8, 11, 14], $generated);
    }
    
    public function test_SequentialInt_can_be_empty(): void
    {
        $producer = Producers::sequentialInt(1, 1, 0);
        
        self::assertTrue($producer->isEmpty());
        self::assertTrue($producer->isCountable());
        self::assertSame(0, $producer->count());
        self::assertNull($producer->getLast());
    }
    
    /**
     * @dataProvider getDataForTestRandomAndSequentialProducersAreReusable
     */
    public function test_random_and_sequential_producers_are_reusable(Producer $producer): void
    {
        self::assertSame(3, $producer->stream()->count()->get());
        self::assertSame(3, $producer->stream()->count()->get());
    }
    
    public function getDataForTestRandomAndSequentialProducersAreReusable(): array
    {
        return [
            'sequentialInt' => [Producers::sequentialInt(1, 1, 3)],
            'randomString' => [Producers::randomString(1, 5, 3)],
            'randomUuid' => [Producers::randomUuid(true, 3)],
            'randomInt' => [Producers::randomInt(1, 10, 3)],
        ];
    }
    
    public function test_PusProducer_can_hold_other_producers(): void
    {
        $push = new PushProducer(false);
        $producer1 = Producers::getAdapter(['a', 'b']);
        $producer2 = Producers::sequentialInt();
        
        $push->addProducer($producer1);
        $push->addProducer($producer2);
        
        self::assertSame([$producer1, $producer2], $push->getProducers());
    }
    
    public function test_ForwardItemsIterator_knows_number_of_elements_and_can_return_the_last_one(): void
    {
        $producer = new ForwardItemsIterator();
        
        self::assertTrue($producer->isEmpty());
        self::assertSame(0, $producer->count());
        self::assertNull($producer->getLast());
        
        $producer->with([new Item(3, 'a'), new Item(1, 'c')]);
        
        self::assertFalse($producer->isEmpty());
        self::assertSame(2, $producer->count());
        self::assertEquals(new Item(1, 'c'), $producer->getLast());
    }
    
    public function test_NonCountableProducer_producer_throws_exception_on_count(): void
    {
        $producer = Producers::collatz();
        self::assertInstanceOf(NonCountableProducer::class, $producer);
        
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('NonCountableProducer cannot count how many elements can produce!');
        
        $producer->count();
    }
    
    public function test_ReverseArrayIterator_empty(): void
    {
        $producer = new ReverseArrayIterator([]);
        
        self::assertTrue($producer->isEmpty());
        self::assertTrue($producer->isCountable());
        self::assertSame(0, $producer->count());
        self::assertNull($producer->getLast());
    }
    
    public function test_ReverseArrayIterator_notEmpty_preserveKeys(): void
    {
        $producer = new ReverseArrayIterator([3 => 'a', 'b', 'c']);
        
        self::assertFalse($producer->isEmpty());
        self::assertSame(3, $producer->count());
        self::assertEquals(new Item(3, 'a'), $producer->getLast());
        
        self::assertSame([
            5 => 'c',
            4 => 'b',
            3 => 'a',
        ], $producer->stream()->toArrayAssoc());
    }
    
    public function test_ReverseArrayIterator_notEmpty_reindex(): void
    {
        $producer = new ReverseArrayIterator([3 => 'a', 'b', 'c'], true);
        
        self::assertFalse($producer->isEmpty());
        self::assertSame(3, $producer->count());
        self::assertEquals(new Item(2, 'a'), $producer->getLast());
        
        self::assertSame([
            0 => 'c',
            1 => 'b',
            2 => 'a',
        ], $producer->stream()->toArrayAssoc());
    }
    
    public function test_ReverseItemsIterator_can_reindex_keys(): void
    {
        //given
        $items = $this->convertToItems([
            3 => 'a',
            2 => 'b',
            4 => 'c',
            1 => 'b',
            0 => 'a',
        ]);
        
        //when
        $producer = new ReverseItemsIterator($items, true);
        
        //then
        self::assertEquals(new Item(4, 'a'), $producer->getLast());
        self::assertSame(['a', 'b', 'c', 'b', 'a'], $producer->stream()->toArrayAssoc());
    }
    
    public function test_ReverseItemsIterator_returns_null_from_getLast_when_is_empty(): void
    {
        $producer = new ReverseItemsIterator([]);
        
        self::assertNull($producer->getLast());
    }
    
    public function test_ReverseNumericalArrayIterator_can_return_the_last_element(): void
    {
        $producer = new ReverseNumericalArrayIterator(['a', 'b', 'c']);
        
        self::assertEquals(new Item(0, 'a'), $producer->getLast());
    }
    
    public function test_ReverseNumericalArrayIterator_can_return_the_last_element_reindexed(): void
    {
        $producer = new ReverseNumericalArrayIterator(['a', 'b', 'c'], true);
        
        self::assertEquals(new Item(2, 'a'), $producer->getLast());
    }
    
    public function test_ReverseNumericalArrayIterator_returns_null_from_getLast_when_is_empty(): void
    {
        $producer = new ReverseNumericalArrayIterator([]);
        
        self::assertNull($producer->getLast());
    }
    
    public function test_ReverseNumericalArrayIterator_no_reindex(): void
    {
        $producer = new ReverseNumericalArrayIterator(['a', 'b', 'c', 'd']);
        
        self::assertSame([3 => 'd', 2 => 'c', 1 => 'b', 0 => 'a'], $producer->stream()->toArrayAssoc());
    }
    
    public function test_ReverseNumericalArrayIterator_with_reindex(): void
    {
        $producer = new ReverseNumericalArrayIterator(['a', 'b', 'c', 'd'], true);
        
        self::assertSame(['d', 'c', 'b', 'a'], $producer->stream()->toArrayAssoc());
    }
    
    public function test_BucketListIterator_returns_null_from_getLast_when_is_empty(): void
    {
        $producer = new BucketListIterator([]);
        
        self::assertSame(0, $producer->count());
        self::assertNull($producer->getLast());
    }
    
    public function test_QueueProducer(): void
    {
        //given
        $queue = Producers::queue(['a', 'b']);
        
        self::assertFalse($queue->isEmpty());
        self::assertSame(2, $queue->count());
        
        //when
        self::assertSame(['a', 'b'], $queue->stream()->toArray());
        
        //then
        self::assertTrue($queue->isEmpty());
        self::assertSame(0, $queue->count());
        self::assertEmpty($queue->stream()->toArray());
        
        //when
        $queue->appendMany(['c', 'd']);
        
        //then
        self::assertFalse($queue->isEmpty());
        self::assertSame(2, $queue->count());
        
        //when
        self::assertSame(['c', 'd'], $queue->stream()->toArray());
        
        //then
        self::assertTrue($queue->isEmpty());
        self::assertSame(0, $queue->count());
        self::assertEmpty($queue->stream()->toArray());
    }
    
    /**
     * @dataProvider getDataForTestCombinedArraysNotEmpty
     */
    public function test_CombinedArrays_not_empty(CombinedArrays $producer): void
    {
        self::assertFalse($producer->isEmpty());
        self::assertSame(2, $producer->count());
        self::assertEquals(new Item('b', 2), $producer->getLast());
    }
    
    public function getDataForTestCombinedArraysNotEmpty(): array
    {
        return [
            [Producers::combinedFrom(['a', 'b'], [5, 2])],
            [Producers::combinedFrom(['a', 'b'], [5, 2, 4])],
            [Producers::combinedFrom(['a', 'b', 'c'], [5, 2])],
        ];
    }
    
    /**
     * @dataProvider getDataForTestCombinedArraysEmpty
     */
    public function test_CombinedArrays_empty(CombinedArrays $producer): void
    {
        self::assertTrue($producer->isEmpty());
        self::assertSame(0, $producer->count());
        self::assertNull($producer->getLast());
    }
    
    public function getDataForTestCombinedArraysEmpty(): array
    {
        return [
            [Producers::combinedFrom([], [5, 2])],
            [Producers::combinedFrom(['a', 'b'], [])],
            [Producers::combinedFrom([], [])],
        ];
    }
    
    /**
     * @dataProvider getDataForTestCombinedGeneralNotEmpty
     */
    public function test_CombinedGeneral_not_empty(CombinedGeneral $producer): void
    {
        self::assertFalse($producer->isEmpty());
        self::assertNull($producer->getLast());
        
        if ($producer->isCountable()) {
            self::assertSame(2, $producer->count());
        }
    }
    
    public function getDataForTestCombinedGeneralNotEmpty(): array
    {
        return [
            [Producers::combinedFrom(['a', 'b'], Stream::from([5, 2]))],
            [Producers::combinedFrom(['a', 'b'], static fn(): array => [5, 2, 4])],
            [Producers::combinedFrom(['a', 'b', 'c'], Producers::getAdapter([5, 2]))],
        ];
    }
    
    /**
     * @dataProvider getDataForTestCombinedGeneralEmpty
     */
    public function test_CombinedGeneral_empty(CombinedGeneral $producer): void
    {
        self::assertFalse($producer->isEmpty());
        self::assertNull($producer->getLast());
        
        if ($producer->isCountable()) {
            self::assertSame(0, $producer->count());
        }
    }
    
    public function getDataForTestCombinedGeneralEmpty(): array
    {
        $emptyArray = static fn(): array => [];
        
        return [
            [Producers::combinedFrom($emptyArray, [5, 2])],
            [Producers::combinedFrom(['a', 'b'], $emptyArray)],
            [Producers::combinedFrom($emptyArray, Stream::empty())],
        ];
    }
    
    public function test_make_stream_from_CombinedGeneral(): void
    {
        $producer = Producers::combinedFrom(
            static fn(): array => ['a', 2, 'b', 1, 'c'],
            Producers::getAdapter([0, '2', 5, 'd', 4, 'e'])
        );
        
        $result = $producer->stream()
            ->onlyStrings()
            ->onlyIntegers(Check::KEY)
            ->toArrayAssoc();
        
        self::assertSame([2 => '2', 1 => 'd'], $result);
    }
    
    public function test_CombinedGeneral_throws_exception_when_is_non_countable_and_method_count_is_called(): void
    {
        //Assert
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('CombinedGeneral producer cannot count how many elements can produce!');
        
        //Arrange
        $producer = Producers::combinedFrom(
            static fn(): array => ['a', 2, 'b', 1, 'c'],
            Producers::getAdapter([0, '2', 5, 'd', 4, 'e'])
        );
        
        //Act
        $producer->count();
    }
    
    /**
     * @return Item[]
     */
    private function convertToItems(array $data): array
    {
        $items = [];
        
        foreach ($data as $key => $value) {
            $items[] = new Item($key, $value);
        }
        
        return $items;
    }
}