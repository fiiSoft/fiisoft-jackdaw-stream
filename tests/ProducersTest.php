<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate\Bucket;
use FiiSoft\Jackdaw\Producer\Generator\CombinedArrays;
use FiiSoft\Jackdaw\Producer\Generator\CombinedGeneral;
use FiiSoft\Jackdaw\Producer\Generator\Exception\GeneratorExceptionFactory;
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Exception\UuidUnavailableException;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidProvider;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidVersion;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate\BucketListIterator;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\CircularBufferIterator;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\MultiProducer;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Producer\Resource\PDOStatementAdapter;
use FiiSoft\Jackdaw\Producer\Resource\TextFileReader;
use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface as RamseyUuid;
use Symfony\Component\Uid\AbstractUid as SymfonyUuid;

final class ProducersTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_wrong_param(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('producer'));
        
        Producers::getAdapter('wrong_argument');
    }
    
    public function test_RandomInt_generator(): void
    {
        $producer = Producers::randomInt(1, 500, 10);
        $count = 0;
        
        $item = new Item();
        foreach (Helper::createItemProducer($item, $producer) as $_) {
            self::assertIsInt($item->value);
            self::assertTrue($item->value >= 1);
            self::assertTrue($item->value <= 500);
            ++$count;
        }
        
        self::assertSame(10, $count);
    }
    
    public function test_RandomString_generator(): void
    {
        $producer = Producers::randomString(3, 10, 5);
        $count = 0;
    
        foreach ($producer as $key => $value) {
            self::assertSame($count++, $key);
            self::assertIsString($value);
            self::assertTrue(\strlen($value) >= 3);
            self::assertTrue(\strlen($value) <= 10, 'length is '.\strlen($value));
        }
    
        self::assertSame(5, $count);
    }
    
    public function test_RandomUuid_generator(): void
    {
        $this->checkIfRamseyOrSymfonyUuidIsInstalled();
        
        $expectedUuidLength = $this->expectedDefaultUuidLength();
        
        $producer = Producers::randomUuid(5);
        $count = 0;
    
        foreach ($producer as $key => $value) {
            self::assertSame($count++, $key);
            self::assertIsString($value);
            self::assertSame($expectedUuidLength, \strlen($value));
        }
        
        self::assertSame(5, $count);
    }
    
    private function checkIfRamseyOrSymfonyUuidIsInstalled(): void
    {
        if (!\interface_exists(RamseyUuid::class) && !\class_exists(SymfonyUuid::class)) {
            self::markTestSkipped('Neither ramsey/uuid or symfony/uid is required to run this test');
        }
    }
    
    public function test_SequentialInt_generator_throws_exception_on_param_step_zero(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('step'));
        
        Producers::sequentialInt(1, 0, 10);
    }
    
    public function test_SequentialInt_generator_throws_exception_on_invalid_param_limit(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('limit'));
        
        Producers::sequentialInt(1, 1, -1);
    }
    
    public function test_RandomString_throws_exception_on_invalid_limit(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('limit'));
        
        Producers::randomString(1, 10, -1);
    }
    
    public function test_RandomString_throws_exception_when_maxLength_is_less_than_minLength(): void
    {
        $this->expectExceptionObject(GeneratorExceptionFactory::maxLengthCannotBeLessThanMinLength());
        
        Producers::randomString(11, 10, 1);
    }
    
    public function test_RandomString_can_generate_string_of_const_length(): void
    {
        $producer = Producers::randomString(5, 5, 3);
        $item = new Item();
    
        foreach (Helper::createItemProducer($item, $producer) as $_) {
            self::assertSame(5, \strlen($item->value));
        }
    }
    
    public function test_RandomInt_throws_exception_on_invalid_limit(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('limit'));
        
        Producers::randomInt(1, 2, -1);
    }
    
    public function test_RandomInt_thows_exception_when_max_is_not_greater_than_min(): void
    {
        $this->expectExceptionObject(GeneratorExceptionFactory::maxCannotBeLessThanOrEqualToMin());
        
        Producers::randomInt(2, 2);
    }
    
    public function test_Collatz_generator_with_known_initial_value_gives_predicable_series_of_numbers(): void
    {
        $buffer = [];
    
        foreach (Producers::collatz(3) as $value) {
            $buffer[] = $value;
        }
    
        self::assertSame([3, 10, 5, 16, 8, 4, 2, 1], $buffer);
    }
    
    public function test_Collatz_generator_with_random_initial_value(): void
    {
        $buffer = [];
    
        foreach (Producers::collatz() as $value) {
            $buffer[] = $value;
        }
    
        $expected = [16, 8, 4, 2, 1];
    
        if (\count($buffer) < \count($expected)) {
            $expected = \array_slice($expected, -\count($buffer));
        }
        
        self::assertSame($expected, \array_slice($buffer, -\count($expected)));
    }
    
    public function test_Collatz_generator_throws_exception_when_initial_number_is_below_one(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('startNumber'));
        
        Producers::collatz(0);
    }
    
    public function test_RandomUuid_generator_throws_exception_when_limit_is_less_than_zero(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('limit'));
        
        Producers::randomUuid(-1);
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
    
        foreach (Helper::createItemProducer($item, $producer) as $_) {
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
        foreach (Helper::createItemProducer($item, $producer) as $_) {
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
        $this->expectExceptionObject(InvalidParamException::byName('resource'));
        
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
    
        foreach (Helper::createItemProducer($item, $producer) as $_) {
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
        foreach (Helper::createItemProducer($item, $producer) as $_) {
            //just iterate
        }
        
        self::assertIsClosedResource($fp);
    }
    
    public function test_resource_reader_throws_exception_when_param_readBytes_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('readBytes'));
    
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
        $this->expectExceptionObject(GeneratorExceptionFactory::invalidParamLevel(-1, Flattener::MAX_LEVEL));
        
        Producers::flattener()->increaseLevel(-1);
    }
    
    public function test_CircularBufferIterator_throws_exception_when_param_buffer_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('buffer'));
        
        new CircularBufferIterator('wrong buffer', 3, 3);
    }
    
    public function test_CircularBufferIterator_throws_exception_when_param_count_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('count'));
        
        new CircularBufferIterator([], -1, 3);
    }
    
    public function test_CircularBufferIterator_throws_exception_when_param_index_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('index'));
        
        new CircularBufferIterator([], 5, 6);
    }
    
    public function test_Flattener_prevents_decrease_level_when_it_is_0(): void
    {
        $this->expectExceptionObject(GeneratorExceptionFactory::cannotDecreaseLevel());
        
        Producers::flattener([], 0)->decreaseLevel();
    }
    
    public function test_Flattener_prevents_decrease_level_when_it_is_1(): void
    {
        $this->expectExceptionObject(GeneratorExceptionFactory::cannotDecreaseLevel());
        
        Producers::flattener([], 1)->decreaseLevel();
    }
    
    public function test_Producer_can_create_Stream_to_iterate_over_values_produced_by_itself(): void
    {
        self::assertSame('A,B,C', Producers::getAdapter(['a', 'b', 'c'])->stream()->map('\strtoupper')->toString());
    }
    
    public function test_reusable_Producer_can_crate_Stream_multiple_times(): void
    {
        $producer = Producers::getAdapter(['a', 1, 'b', 2, 'c', 3]);
        
        self::assertSame([1, 2, 3], $producer->stream()->filter('\is_int')->toArray());
        self::assertSame(['a', 'b', 'c'], $producer->stream()->filter('\is_string')->toArray());
    }
    
    public function test_QueueProducer_details(): void
    {
        $item = new Item();
        
        $producer = Producers::queue();
        $producer->appendMany(['a', 'b', 'c']); //0:a,1:b,2:c
        
        $generator = Helper::createItemProducer($item, $producer);
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
        
        self::assertSame(0, \iterator_count($producer));
    }
    
    public function test_MultiProducer(): void
    {
        $producer = Producers::multiSourced(
            ['a', 'b'],
            Producers::sequentialInt(1, 1, 2)
        );
        
        self::assertSame(['a', 'b', 1, 2], $producer->stream()->toArray());
        self::assertSame(['a', 'b', 1, 2], $producer->stream()->toArray());
    }
    
    public function test_MultiProducer_can_be_empty_only_when_all_producers_within_it_are_empty(): void
    {
        self::assertSame(
            0,
            \iterator_count(Producers::multiSourced([], Producers::sequentialInt(1, 1, 0), Producers::queue()))
        );
    }
    
    public function test_CircularBufferIterator(): void
    {
        $cbi = new CircularBufferIterator([], 0, 0);
        self::assertSame(0, \iterator_count($cbi));
        
        $cbi = new CircularBufferIterator(self::items(['a']), 1, 0);
        self::assertGreaterThan(0, \iterator_count($cbi));
        
        $cbi = new CircularBufferIterator(new \ArrayObject(), 0, 0);
        self::assertSame(0, \iterator_count($cbi));
        
        $cbi = new CircularBufferIterator(new \ArrayObject(self::items(['a'])), 1, 0);
        self::assertGreaterThan(0, \iterator_count($cbi));
        
        $cbi = new CircularBufferIterator(new \SplFixedArray(5), 0, 0);
        self::assertSame(0, \iterator_count($cbi));
        
        $buffer = new \SplFixedArray(5);
        $buffer[0] = new Item(3, 'a');
        $cbi = new CircularBufferIterator($buffer, 1, 0);
        self::assertGreaterThan(0, \iterator_count($cbi));
    }
    
    public function test_ReverseItemsIterator_can_tell_if_is_empty(): void
    {
        $rii = new ReverseItemsIterator([]);
        self::assertSame(0, \iterator_count($rii));
        
        $rii = new ReverseItemsIterator([new Item(0, 1)]);
        self::assertGreaterThan(0, \iterator_count($rii));
    }
    
    public function test_MultiProducer_can_merge_other_MultiProducer(): void
    {
        $first = Producers::multiSourced(['a', 'b'], new \ArrayIterator([1, 2]));
        $second = Producers::multiSourced($first, new \ArrayObject(['foo', 'bar']));
        
        self::assertSame(['a', 'b', 1, 2, 'foo', 'bar'], $second->stream()->toArray());
    }
    
    public function test_ArrayIteratorAdapter_not_empty(): void
    {
        self::assertCount(3, Producers::getAdapter(new \ArrayIterator(['a', 'b', 'c'])));
    }
    
    public function test_ArrayIteratorAdapter_empty(): void
    {
        self::assertCount(0, Producers::getAdapter(new \ArrayIterator()));
    }
    
    public function test_SequentialInt_can_be_empty(): void
    {
        self::assertSame(0, \iterator_count(Producers::sequentialInt(1, 1, 0)));
    }
    
    public function test_default_uuid_producer_throws_exception_when_version_is_invalid(): void
    {
        $this->expectExceptionObject(UuidUnavailableException::create());
        
        Producers::uuidFrom(UuidProvider::version(UuidVersion::nil()));
    }
    
    public function test_symfony_uuid_producer_throws_exception_when_version_is_invalid(): void
    {
        $this->expectExceptionObject(UuidUnavailableException::create());
        
        Producers::uuidFrom(UuidProvider::symfony(UuidVersion::nil()));
    }
    
    public function test_ramsey_uuid_producer_throws_exception_when_version_is_invalid(): void
    {
        $this->expectExceptionObject(UuidUnavailableException::create());
        
        Producers::uuidFrom(UuidProvider::ramsey(UuidVersion::nil()));
    }
    
    /**
     * @dataProvider getDataForTestRandomAndSequentialProducersAreReusable
     */
    #[DataProvider('getDataForTestRandomAndSequentialProducersAreReusable')]
    public function test_random_and_sequential_producers_are_reusable(Producer $producer): void
    {
        self::assertSame(3, $producer->stream()->count()->get());
        self::assertSame(3, $producer->stream()->count()->get());
    }
    
    public static function getDataForTestRandomAndSequentialProducersAreReusable(): \Generator
    {
        yield 'sequentialInt' => [Producers::sequentialInt(1, 1, 3)];
        yield 'randomString' => [Producers::randomString(1, 5, 3)];
        yield 'randomInt' => [Producers::randomInt(1, 10, 3)];
    }
    
    public function test_default_uuidV4_producer(): void
    {
        $this->checkIfRamseyOrSymfonyUuidIsInstalled();
        
        $this->examineUuidProducer(Producers::uuidV4(), $this->expectedDefaultUuidLength());
    }
    
    public function test_uuid_producers_are_reusable(): void
    {
        $this->checkIfRamseyOrSymfonyUuidIsInstalled();
        
        if (\interface_exists(RamseyUuid::class)) {
            
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramsey()));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramsey(UuidVersion::v6())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramsey(UuidVersion::v4())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramsey(UuidVersion::v1())));
            
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramseyHex()));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramseyHex(UuidVersion::v6())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramseyHex(UuidVersion::v4())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::ramseyHex(UuidVersion::v1())));
        }
        
        if (\class_exists(SymfonyUuid::class)) {
            
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfony()));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfony(UuidVersion::v6())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfony(UuidVersion::v4())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfony(UuidVersion::v1())));
            
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase32()));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase32(UuidVersion::v6())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase32(UuidVersion::v4())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase32(UuidVersion::v1())));
            
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase58()));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase58(UuidVersion::v6())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase58(UuidVersion::v4())));
            $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::symfonyBase58(UuidVersion::v1())));
        }
        
        $this->examineReusableUuidProducers(Producers::randomUuid());
        
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default()));
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default(false)));
        
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default(true, UuidVersion::v6())));
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default(true, UuidVersion::v4())));
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default(true, UuidVersion::v1())));
        
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default(false, UuidVersion::v6())));
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default(false, UuidVersion::v4())));
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::default(false, UuidVersion::v1())));
        
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::version(UuidVersion::v6())));
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::version(UuidVersion::v4())));
        $this->examineReusableUuidProducers(Producers::uuidFrom(UuidProvider::version(UuidVersion::v1())));
    }
    
    private function examineReusableUuidProducers(Producer $producer): void
    {
        $uuids1 = $producer->stream()->limit(3)->toArray();
        $uuids2 = $producer->stream()->limit(3)->toArray();
        
        self::assertNotSame($uuids1, $uuids2);
    }
    
    public function test_UuidProvider_ramsey_default(): void
    {
        $this->checkIfRamseyUuidIsInstalled();
        
        $producer = Producers::uuidFrom(UuidProvider::ramsey());
        
        $this->examineUuidProducer($producer, 36);
    }
    
    public function test_UuidProvider_ramsey_hex(): void
    {
        $this->checkIfRamseyUuidIsInstalled();
        
        $producer = Producers::uuidFrom(UuidProvider::ramseyHex());
        
        $this->examineUuidProducer($producer, 32);
    }
    
    private function checkIfRamseyUuidIsInstalled(): void
    {
        if (!\interface_exists(RamseyUuid::class)) {
            self::markTestSkipped('Test skipped because ramsey/uuid is not installed');
        }
    }
    
    public function test_UuidProvider_symfony_default(): void
    {
        $this->checkIfSymfonyUuidIsInstalled();
        
        $producer = Producers::uuidFrom(UuidProvider::symfony());
        
        $this->examineUuidProducer($producer, 36);
    }
    
    public function test_UuidProvider_symfony_base32(): void
    {
        $this->checkIfSymfonyUuidIsInstalled();
        
        $producer = Producers::uuidFrom(UuidProvider::symfonyBase32());
        
        $this->examineUuidProducer($producer, 26);
    }
    
    public function test_UuidProvider_symfony_base58(): void
    {
        $this->checkIfSymfonyUuidIsInstalled();
        
        $producer = Producers::uuidFrom(UuidProvider::symfonyBase58());
        
        $this->examineUuidProducer($producer, 22);
    }
    
    private function examineUuidProducer(Producer $producer, int $length): void
    {
        $uuids = [];
        
        foreach ($producer->stream()->limit(3) as $uuid) {
            self::assertIsString($uuid);
            self::assertSame($length, \strlen($uuid));
            $uuids[] = $uuid;
        }
        
        self::assertNotSame($uuids[0], $uuids[1]);
        self::assertNotSame($uuids[1], $uuids[2]);
        self::assertNotSame($uuids[0], $uuids[2]);
    }
    
    private function checkIfSymfonyUuidIsInstalled(): void
    {
        if (!\class_exists(SymfonyUuid::class)) {
            self::markTestSkipped('Test skipped because symfony/uid is not installed');
        }
    }
    
    public function test_MultiProducer_can_hold_other_producers(): void
    {
        $push = MultiProducer::oneTime();
        $producer1 = Producers::getAdapter(['a', 'b']);
        $producer2 = Producers::sequentialInt();
        
        $push->addProducer($producer1);
        $push->addProducer($producer2);
        
        self::assertSame([$producer1, $producer2], $push->getProducers());
    }
    
    public function test_ForwardItemsIterator(): void
    {
        $producer = new ForwardItemsIterator();
        self::assertSame(0, \iterator_count($producer));

        $producer->with([new Item(3, 'a'), new Item(1, 'c')]);
        self::assertSame(2, \iterator_count($producer));
    }
    
    public function test_ReverseItemsIterator_can_reindex_keys(): void
    {
        //given
        $items = self::items([
            3 => 'a',
            2 => 'b',
            4 => 'c',
            1 => 'b',
            0 => 'a',
        ]);
        
        //when
        $producer = new ReverseItemsIterator($items, true);
        
        //then
        self::assertSame(['a', 'b', 'c', 'b', 'a'], $producer->stream()->toArrayAssoc());
    }
    
    public function test_BucketListIterator_empty(): void
    {
        self::assertSame(0, \iterator_count(new BucketListIterator([])));
    }
    
    public function test_QueueProducer(): void
    {
        $queue = Producers::queue(['a', 'b']);
        
        self::assertSame(['a', 'b'], $queue->stream()->toArray());
        self::assertEmpty($queue->stream()->toArray());
        
        $queue->appendMany(['c', 'd']);
        self::assertSame(['c', 'd'], $queue->stream()->toArray());
        self::assertEmpty($queue->stream()->toArray());
    }
    
    /**
     * @dataProvider getDataForTestCombinedArraysNotEmpty
     */
    #[DataProvider('getDataForTestCombinedArraysNotEmpty')]
    public function test_CombinedArrays_not_empty(CombinedArrays $producer): void
    {
        self::assertSame(2, \iterator_count($producer));
    }
    
    public static function getDataForTestCombinedArraysNotEmpty(): array
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
    #[DataProvider('getDataForTestCombinedArraysEmpty')]
    public function test_CombinedArrays_empty(CombinedArrays $producer): void
    {
        self::assertSame(0, \iterator_count($producer));
    }
    
    public static function getDataForTestCombinedArraysEmpty(): array
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
    #[DataProvider('getDataForTestCombinedGeneralNotEmpty')]
    public function test_CombinedGeneral_not_empty(CombinedGeneral $producer): void
    {
        self::assertSame(2, \iterator_count($producer));
    }
    
    public static function getDataForTestCombinedGeneralNotEmpty(): array
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
    #[DataProvider('getDataForTestCombinedGeneralEmpty')]
    public function test_CombinedGeneral_empty(CombinedGeneral $producer): void
    {
        self::assertSame(0, \iterator_count($producer));
    }
    
    public static function getDataForTestCombinedGeneralEmpty(): array
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
    
    public function test_UuidGenerator_can_be_use_as_producer(): void
    {
        //given
        $producer = Producers::getAdapter(UuidProvider::symfonyBase58());
     
        //when
        $uuid = Stream::from($producer)->first()->get();
        
        //then
        self::assertIsString($uuid);
        self::assertSame(22, \strlen($uuid));
    }
    
    public function test_ReferenceAdapter_can_use_variable_as_source_of_data_for_stream(): void
    {
        $var = 11;
        $numbers = [];
        
        Producers::readFrom($var)
            ->stream()
            ->while(Filters::greaterThan(0))
            ->storeIn($numbers)
            ->map(static fn(int $v): int => (int) ($v / 2))
            ->putIn($var)
            ->run();
        
        self::assertSame([11, 5, 2, 1], $numbers);
    }
    
    public function test_ReferenceAdapter_stops_iterating_when_value_of_variable_is_null(): void
    {
        $var = 3;
        $values = [];
        
        $count = Producers::readFrom($var)
            ->stream()
            ->call(static function () use (&$var, &$values) {
                $values[] = $var;
                if (--$var === 0) {
                    $var = null;
                }
            })
            ->count();
        
        self::assertSame(3, $count->get());
        self::assertSame([3, 2, 1], $values);
    }
    
    public function test_RegistryAdapter_allows_to_use_Registry_as_source_of_stream(): void
    {
        $reg = Registry::new()->set('val', 11);
        $numbers = [];
        
        Producers::getAdapter($reg->read('val'))
            ->stream()
            ->while(Filters::greaterThan(0))
            ->storeIn($numbers)
            ->map(static fn(int $v): int => (int) ($v / 2))
            ->remember($reg->value('val'))
            ->run();
        
        self::assertSame([11, 5, 2, 1], $numbers);
    }
    
    public function test_RegistryAdapter_stops_iterating_when_read_value_is_null(): void
    {
        $reg = Registry::new()->set('val', 3);
        $values = [];
        
        $count = Producers::getAdapter($reg->read('val'))
            ->stream()
            ->storeIn($values)
            ->map(static fn(int $v): ?int => --$v !== 0 ? $v : null)
            ->remember($reg->value('val'))
            ->count();
        
        self::assertSame(3, $count->get());
        self::assertSame([3, 2, 1], $values);
    }
    
    /**
     * @dataProvider getDataForTestIterateProducer
     */
    #[DataProvider('getDataForTestIterateProducer')]
    public function test_iterate_producer($producer): void
    {
        self::assertSame([1 => 'a', 3 => 'b', 5 => 'c'], \iterator_to_array(Producers::getAdapter($producer)));
    }
    
    public static function getDataForTestIterateProducer(): \Generator
    {
        $data = [1 => 'a', 3 => 'b', 5 => 'c'];

        yield 'ArrayAdapter' => [$data];
        yield 'ArrayIteratorAdapter' => [new \ArrayIterator($data)];

        yield 'CircularBufferIterator' => [
            new CircularBufferIterator(self::items([5 => 'c', 1 => 'a', 3 => 'b']), 3, 1)
        ];

        yield 'CombinedArrays' => [Producers::combinedFrom([1, 3, 5], ['a', 'b', 'c'])];

        yield 'CombinedGeneral' => [
            Producers::combinedFrom(
                Producers::sequentialInt(1, 2),
                Producers::tokenizer(' ', 'a b c')
            )
        ];

        yield 'ForwardItemsIterator' => [new ForwardItemsIterator(self::items($data))];

        yield 'QueueProducer' => [Producers::queue()->append('a', 1)->append('b', 3)->append('c', 5)];

        yield 'ReverseItemsIterator' => [new ReverseItemsIterator(\array_reverse(self::items($data)))];
        
        yield 'MultiProducer' => [
            Producers::multiSourced(
                Producers::queue()->append('a', 1),
                Producers::combinedFrom([3], ['b']),
                [5 => 'c']
            )
        ];
        
        yield 'CallableAdapter' => [
            Producers::getAdapter(static function () use ($data) {
                yield from $data;
            })
        ];
        
        yield 'Flattener' => [Producers::flattener([1 => 'a', [3 => 'b', [5 => 'c']]])];
        
        yield 'ResultCasterAdapter' => [Producers::getAdapter(Stream::from($data)->collect())];
    }
    
    public function test_iterate_BucketListIterator_producer(): void
    {
        $data = ['a', 'b', 'c'];
        
        $bucket = new Bucket();
        $bucket->data = $data;
        
        self::assertSame([$data], \iterator_to_array(new BucketListIterator([$bucket])));
    }
    
    public function test_iterate_RandomInt_producer(): void
    {
        $expectedKey = 0;
        
        foreach (Producers::randomInt(5, 9, 3) as $key => $value) {
            self::assertSame($expectedKey++, $key);
            self::assertGreaterThanOrEqual(5, $value);
            self::assertLessThanOrEqual(9, $value);
        }
        
        self::assertSame(3, $expectedKey);
    }
    
    public function test_iterate_RandomString_producer(): void
    {
        $expectedKey = 0;
        
        foreach (Producers::randomString(5, 9, 3, 'abcdefghijkl') as $key => $value) {
            self::assertSame($expectedKey++, $key);
            self::assertMatchesRegularExpression('/^[abcdefghijkl]{5,9}$/', $value);
        }
        
        self::assertSame(3, $expectedKey);
    }
    
    public function test_iterate_RandomString_producer_with_constant_string_length(): void
    {
        $expectedKey = 0;
        
        foreach (Producers::randomString(5, 5, 3, 'abcdefghijkl') as $key => $value) {
            self::assertSame($expectedKey++, $key);
            self::assertMatchesRegularExpression('/^[abcdefghijkl]{5}$/', $value);
        }
        
        self::assertSame(3, $expectedKey);
    }
    
    public function test_iterate_RandomUuid_producer(): void
    {
        $this->checkIfRamseyOrSymfonyUuidIsInstalled();
        
        $expectedUuidLength = $this->expectedDefaultUuidLength();
        $expectedKey = 0;
        
        foreach (Producers::randomUuid(3) as $key => $value) {
            self::assertSame($expectedKey++, $key);
            self::assertSame($expectedUuidLength, \strlen($value));
        }
        
        self::assertSame(3, $expectedKey);
    }
    
    public function test_iterate_SequentialInt_producer(): void
    {
        self::assertSame([3, 5, 7], \iterator_to_array(Producers::sequentialInt(3, 2, 3)));
    }
    
    public function test_iterate_IteratorAdapter_producer(): void
    {
        $queue = new \SplQueue();
        $queue->enqueue('a');
        $queue->enqueue('b');
        $queue->enqueue('c');
        
        $producer = Producers::getAdapter($queue);
        
        self::assertSame(['a', 'b', 'c'], \iterator_to_array($producer));
    }
    
    public function test_iterate_CollatzGenerator_producer(): void
    {
        self::assertSame([3, 10, 5, 16, 8, 4, 2, 1], \iterator_to_array(Producers::collatz(3)));
    }
    
    public function test_iterate_PDOStatementAdapter_producer(): void
    {
        $stmt = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $stmt->expects(self::exactly(4))->method('fetch')->willReturnOnConsecutiveCalls(
            [1 => 'a'],
            [3 => 'b'],
            [5 => 'c'],
            false,
        );
        
        self::assertSame([[1 => 'a'], [3 => 'b'], [5 => 'c']], \iterator_to_array(Producers::getAdapter($stmt)));
    }
    
    public function test_iterate_ReferenceAdapter_producer(): void
    {
        $values = [3, 2, 5];
        $current = \array_shift($values);
        
        $actual = [];
        foreach (Producers::readFrom($current) as $key => $value) {
            $actual[$key] = $value;
            $current = \array_shift($values);
        }
        
        self::assertSame([3, 2, 5], $actual);
    }
    
    public function test_iterate_TimeIterator_producer(): void
    {
        $result = [];
        
        foreach (Producers::dateTimeSeq('2024-06-29', '1 day', null, 4) as $dateTime) {
            $result[] = $dateTime->format('Y-m-d');
        }
        
        self::assertSame(['2024-06-29', '2024-06-30', '2024-07-01', '2024-07-02'], $result);
    }
    
    public function test_iterate_RegistryAdapter_producer(): void
    {
        $values = [3, 2, 5];
        
        $regEntry = Registry::new()->entry(Check::VALUE);
        $regEntry->set(\array_shift($values));
        
        $actual = [];
        foreach (Producers::getAdapter($regEntry) as $key => $value) {
            $actual[$key] = $value;
            $regEntry->set(\array_shift($values));
        }
        
        self::assertSame([3, 2, 5], $actual);
    }
    
    public function test_iterate_TextFileReader_producer(): void
    {
        foreach (Producers::resource(\fopen(__FILE__, 'rb')) as $line) {
            self::assertSame('<?php declare(strict_types=1);', \trim($line));
            break;
        }
    }
    
    public function test_TimeIterator_throws_exception_when_param_startDate_is_invalid(): void
    {
        $this->expectExceptionObject(GeneratorExceptionFactory::invalidDateTimeParam('startDate', []));
        
        Producers::dateTimeSeq([]);
    }
    
    public function test_TimeIterator_throws_exception_when_param_endDate_is_invalid(): void
    {
        $this->expectExceptionObject(GeneratorExceptionFactory::invalidDateTimeParam('endDate', []));
        
        Producers::dateTimeSeq(null, null, []);
    }
    
    public function test_TimeIterator_throws_exception_when_param_interval_is_invalid(): void
    {
        $this->expectExceptionObject(GeneratorExceptionFactory::invalidDateIntervalParam('interval', []));
        
        Producers::dateTimeSeq(null, []);
    }
    
    /**
     * @dataProvider getDataForTestIterateOverDateTimeSequence
     */
    #[DataProvider('getDataForTestIterateOverDateTimeSequence')]
    public function test_iterate_over_DateTime_sequence(
        $startDate, $interval, $endDate, ?int $limit, array $expected
    ): void
    {
        $previous = \date_default_timezone_get();
        \date_default_timezone_set('Europe/London');
        
        try {
            $dates = [];
            foreach (Producers::dateTimeSeq($startDate, $interval, $endDate, $limit) as $date) {
                $dates[] = $date;
            }
            
            self::assertCount(\count($expected), $dates);
            
            foreach (\array_keys($expected) as $n) {
                self::assertSame($expected[$n], $dates[$n]->format('Y-m-d H:i:s'));
            }
        } finally {
            \date_default_timezone_set($previous);
        }
    }
    
    public static function getDataForTestIterateOverDateTimeSequence(): array
    {
        $previous = \date_default_timezone_get();
        \date_default_timezone_set('Europe/London');
        
        $startImmutable = new \DateTimeImmutable('2020-01-01 12:00:00');
        $startMutable = \DateTime::createFromImmutable($startImmutable);
        $startTimestamp = $startMutable->getTimestamp();
        
        $byDay = ['2020-01-01 12:00:00', '2020-01-02 12:00:00', '2020-01-03 12:00:00'];
        $byHour = ['2020-01-01 12:00:00', '2020-01-01 11:00:00', '2020-01-01 10:00:00'];
        $byMonth = ['2020-01-01 12:00:00', '2020-02-01 12:00:00', '2020-03-01 12:00:00', '2020-04-01 12:00:00'];
        
        $decrementByOneHour = new \DateInterval('PT1H');
        $decrementByOneHour->invert = 1;
        
        //startDate, interval, endDate, limit, expected
        $testData = [
            //increment by 1 day three times
            [$startImmutable, null, null, 3, $byDay],
            [$startMutable, '+1 day', null, 3, $byDay],
            [$startImmutable, new \DateInterval('P1D'), null, 3, $byDay],
            //increment by 1 day until endDate is reached
            [$startImmutable, null, $byDay[2], null, $byDay],
            [$startImmutable, null, $byDay[2], 3, $byDay],
            [$startMutable, '+1 day', $byDay[2], null, $byDay],
            [$startImmutable, new \DateInterval('P1D'), $byDay[2], null, $byDay],
            //decrement by 1 hour 3 times
            [$startTimestamp, '-1 hour', null, 3, $byHour],
            [$startImmutable, $decrementByOneHour, null, 3, $byHour],
            //decrement by 1 hour until endDate is reached
            [$startImmutable, '-1 hour', $byHour[2], null, $byHour],
            [$startTimestamp, '-1 hour', $byHour[2], 3, $byHour],
            [$startImmutable, new \DateInterval('PT1H'), $byHour[2], null, $byHour],
            //increment by 1 month until endDate
            [$startImmutable, '1 month', '2020-04-30 23:59:59', null, $byMonth],
        ];
        
        \date_default_timezone_set($previous);
        
        return $testData;
    }
    
    public function test_dateTimeSeq_producer_can_be_reused(): void
    {
        $result1 = [];
        $result2 = [];
        
        $producer = Producers::dateTimeSeq('now', '1 hour', null, 3);
        
        foreach ($producer as $date) {
            $result1[] = $date->format('Y-m-d H:i:s');
        }
        
        foreach ($producer as $date) {
            $result2[] = $date->format('Y-m-d H:i:s');
        }
        
        self::assertSame($result1, $result2);
    }
    
    /**
     * @dataProvider getDataForTestDateTimeSeqProducerDoesNotDecrementWhenEndDateIsAfterStartDate
     */
    #[DataProvider('getDataForTestDateTimeSeqProducerDoesNotDecrementWhenEndDateIsAfterStartDate')]
    public function test_dateTimeSeq_producer_does_not_decrement_when_endDate_is_after_startDate($interval): void
    {
        $dates = [];
        
        foreach (Producers::dateTimeSeq('2000-05-15', $interval, '2000-05-17') as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        
        self::assertEmpty($dates);
    }
    
    public static function getDataForTestDateTimeSeqProducerDoesNotDecrementWhenEndDateIsAfterStartDate(): array
    {
        return [
            ['-1 day'],
            [self::decrementInterval('P1D')],
        ];
    }
    
    /**
     * @dataProvider getDataForTestDateTimeSeqProducerAlwaysDecrementsWhenEndDateIsBeforeStartDate
     */
    #[DataProvider('getDataForTestDateTimeSeqProducerAlwaysDecrementsWhenEndDateIsBeforeStartDate')]
    public function test_dateTimeSeq_producer_always_decrements_when_endDate_is_before_startDate($interval): void
    {
        $dates = [];
        
        foreach (Producers::dateTimeSeq('2000-05-17', $interval, '2000-05-15') as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        
        self::assertSame(['2000-05-17', '2000-05-16', '2000-05-15'], $dates);
    }
    
    public static function getDataForTestDateTimeSeqProducerAlwaysDecrementsWhenEndDateIsBeforeStartDate(): array
    {
        return [
            [null],
            ['1 day'],
            [new \DateInterval('P1D')],
            ['-1 day'],
            [self::decrementInterval('P1D')],
        ];
    }
    
    private static function decrementInterval(string $format): \DateInterval
    {
        $decrement = new \DateInterval($format);
        $decrement->invert = 1;
        
        return $decrement;
    }
    
    /**
     * @return Item[]
     */
    private static function items(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = new Item($key, $value);
        }
        
        return $result;
    }
    
    private function expectedDefaultUuidLength(): int
    {
        return \class_exists(SymfonyUuid::class) ? 22 : 32;
    }
}