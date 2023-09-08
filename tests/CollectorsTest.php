<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Collector\DefaultCollector;
use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class CollectorsTest extends TestCase
{
    public function test_getAdapter_returns_passed_Collector(): void
    {
        $collector = Collectors::getAdapter(new \ArrayObject());
        self::assertSame($collector, Collectors::getAdapter($collector));
    }
    
    public function test_iterable_returns_passed_Collector(): void
    {
        $collector = Collectors::iterable(new \ArrayObject());
        self::assertSame($collector, Collectors::iterable($collector));
    }
    
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Collectors::getAdapter(15);
    }
    
    public function test_ierable_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Collectors::iterable(15);
    }
    
    public function test_ArrayObject_collector_is_iterable(): void
    {
        //given
        $collector = Collectors::iterable(new \ArrayObject([1,3,5]));
        
        //when
        $data = [];
        foreach ($collector as $key => $value) {
            $data[$key] = $value;
        }
        
        //then
        self::assertSame([1, 3, 5], $data);
    }
    
    public function test_cloned_collectors_share_instance_of_the_same_wrapped_ArrayObject(): void
    {
        //given
        $collector1 = Collectors::iterable(new \ArrayObject([1,3,5]));
        $collector2 = clone $collector1;
        
        //when
        $collector1->add(7);
        $collector2->add(9);
        
        //then
        self::assertSame([1, 3, 5, 7, 9], $collector1->getData());
        self::assertSame([1, 3, 5, 7, 9], $collector2->getData());
    }
    
    public function test_ArrayObject_collector_can_be_cleared(): void
    {
        //given
        $collector = Collectors::iterable(new \ArrayObject([1,3,5]));
        self::assertSame([1, 3, 5], $collector->getData());
        
        //when
        $collector->clear();
        
        //then
        self::assertEmpty($collector->getData());
    }
    
    public function test_ArrayObject_collector_can_create_stream(): void
    {
        //given
        $collector = Collectors::iterable(new \ArrayObject([1,3,5]));
        
        //when
        $copy = $collector->stream()->toArray();
        
        //then
        self::assertSame([1, 3, 5], $copy);
    }
    
    public function test_default_collector_can_be_cloned(): void
    {
        //given
        $collector1 = Collectors::default();
        $collector2 = clone $collector1;
        
        //when
        $collector1->add('a');
        $collector1->add('b');
        
        $collector2->add('x');
        $collector2->add('y');
        
        //then
        self::assertSame(['a', 'b'], $collector1->getData());
        self::assertSame(['x', 'y'], $collector2->getData());
    }
    
    public function test_default_collector_can_be_emptied(): void
    {
        //given
        $collector = Collectors::default();
        $collector->add('a');
        
        self::assertSame(['a'], $collector->getData());
        
        //when
        $collector->clear();
        
        //then
        self::assertEmpty($collector->getData());
    }
    
    public function test_default_collector_can_be_iterable(): void
    {
        //given
        $collector = Collectors::default();
        $collector->add('a');
        $collector->add('b');
        
        //when
        $data = \iterator_to_array($collector);
        
        //then
        self::assertSame(['a', 'b'], $data);
    }
    
    public function test_default_collector_is_countable(): void
    {
        $collector = Collectors::default();
        self::assertCount(0, $collector);
        
        $collector->add('a');
        self::assertCount(1, $collector);
        
        $collector->add('b');
        self::assertCount(2, $collector);
    }
    
    public function test_each_collectot_can_serialize_collected_values_to_string(): void
    {
        //given
        $collector = Collectors::default();
        $collector->set('a', 1);
        $collector->set('b', 2);
        
        //when
        $str = $collector->toString();
        
        //then
        self::assertSame('1,2', $str);
    }
    
    public function test_each_collectot_can_serialize_collected_values_to_json(): void
    {
        //given
        $collector = Collectors::default();
        $collector->set('a', 1);
        $collector->set('b', 2);
        
        //when
        $json = $collector->toJson();
        
        //then
        self::assertSame('{"a":1,"b":2}', $json);
    }
    
    /**
     * @dataProvider getDataForTestVariousSplClassesCanBeUsedAsCollectors
     */
    public function test_various_SPL_classes_can_be_used_as_collectors($wrapped, array $expected): void
    {
        $collector = Collectors::getAdapter($wrapped, false);
        
        Stream::from([4, 8, 3])->collectIn($collector)->run();
        
        if ($wrapped instanceof \Traversable) {
            self::assertSame($expected, \iterator_to_array($wrapped, false));
        }
        
        //SplHeap and SplPriorityQueue can be iterate only once
        if ($wrapped instanceof \SplHeap || $wrapped instanceof \SplPriorityQueue) {
            return;
        }
        
        //others can iterate again
        if ($wrapped instanceof \Traversable) {
            self::assertSame($expected, \iterator_to_array($wrapped, false));
        }
    }
    
    public static function getDataForTestVariousSplClassesCanBeUsedAsCollectors(): \Generator
    {
        //ArrayAccess
        yield 'SplFixedArray' =>        [new \SplFixedArray(3),      [4, 8, 3]];
        yield 'ArrayIterator' =>        [new \ArrayIterator([]),     [4, 8, 3]];
        yield 'ArrayObject' =>          [new \ArrayObject(),         [4, 8, 3]];
        
        yield 'SplDoublyLinkedList' =>  [new \SplDoublyLinkedList(), [4, 8, 3]];
        yield 'SplQueue' =>             [new \SplQueue(),            [4, 8, 3]];
        yield 'SplStack' =>             [new \SplStack(),            [3, 8, 4]];
        
        //not ArrayAccess
        yield 'SplMaxHeap' =>           [new \SplMaxHeap(),          [8, 4, 3]];
        yield 'SplMinHeap' =>           [new \SplMinHeap(),          [3, 4, 8]];
        yield 'SplPriorityQueue' =>     [new \SplPriorityQueue(),    [4, 3, 8]];
    }
    
    /**
     * @dataProvider getDataForTestSomeCollectorsAreIterable
     */
    public function test_some_collectors_are_iterable_and_countable_and_have_other_features($collector): void
    {
        $collector = Collectors::iterable($collector);
        self::assertCount(0, $collector);
        
        Stream::from([8, 2, 5])->collectIn($collector)->run();
        
        self::assertCount(3, $collector);
        self::assertSame(3, $collector->count());
        
        $expected = [8, 2, 5];
        
        self::assertSame($expected, $collector->getData());
        self::assertSame($expected, \iterator_to_array($collector));
        self::assertSame($expected, $collector->stream()->toArray());
        
        $data = [];
        foreach ($collector as $value) {
            $data[] = $value;
        }
        self::assertSame($expected, $data);
        
        self::assertSame('8,2,5', $collector->toString());
        self::assertSame('[8,2,5]', $collector->toJson());
        
        $collector->clear();
        
        self::assertSame(0, $collector->count());
        self::assertEmpty($collector->getData());
        self::assertSame('', $collector->toString());
        self::assertSame('[]', $collector->toJson());
        self::assertTrue($collector->stream()->isEmpty()->get());
    }
    
    public static function getDataForTestSomeCollectorsAreIterable(): \Generator
    {
        yield 'DefaultCollector' => [Collectors::default()];
        yield 'SplFixedArray'    => [new \SplFixedArray(5)];
        yield 'ArrayIterator'    => [new \ArrayIterator([])];
        yield 'ArrayObject'      => [new \ArrayObject()];
        
        $array = [];
        yield 'array'            => [Collectors::array($array)];
    }
    
    /**
     * @dataProvider getDataForTestSomeCollectorsAllowToPreserveKeys
     */
    public function test_some_collectors_allows_to_preserve_keys($wrapped, array $expected): void
    {
        $collector = Collectors::getAdapter($wrapped);
        
        Stream::from([4 => 'b', 8 => 'c', 3 => 'a'])
            ->collectIn($collector)
            ->run();
        
        if ($wrapped instanceof DefaultCollector) {
            self::assertSame($expected, $wrapped->getData());
        } elseif ($wrapped instanceof \SplFixedArray) {
            if ($collector instanceof IterableCollector) {
                self::assertSame($expected, $collector->getData());
            }
        } elseif ($wrapped instanceof \Traversable) {
            self::assertSame($expected, \iterator_to_array($wrapped));
        }
    }
    
    public static function getDataForTestSomeCollectorsAllowToPreserveKeys(): \Generator
    {
        yield 'DefaultCollector' =>     [Collectors::default(),      [4 => 'b', 8 => 'c', 3 => 'a']];
        yield 'SplFixedArray' =>        [new \SplFixedArray(10),     [3 => 'a', 4 => 'b', 8 => 'c']];
        yield 'ArrayIterator' =>        [new \ArrayIterator([]),     [4 => 'b', 8 => 'c', 3 => 'a']];
        yield 'ArrayObject' =>          [new \ArrayObject(),         [4 => 'b', 8 => 'c', 3 => 'a']];
        yield 'SplPriorityQueue' =>     [new \SplPriorityQueue(),    [2 => 'c', 1 => 'b', 0 => 'a']];
        
        $array = [];
        yield 'array' =>                [Collectors::array($array),  [4 => 'b', 8 => 'c', 3 => 'a']];
    }
    
    /**
     * @dataProvider getDataForTestSomeIterableCollectorsAllowToPreserveKeys
     */
    public function test_some_iterable_collectors_allows_to_preserve_keys($wrapped, array $expected): void
    {
        $collector = Collectors::iterable($wrapped);
        
        Stream::from([4 => 'b', 8 => 'c', 3 => 'a'])
            ->collectIn($collector)
            ->run();
        
        if ($wrapped instanceof DefaultCollector) {
            self::assertSame($expected, $wrapped->getData());
        } elseif ($wrapped instanceof \SplFixedArray) {
            self::assertSame($expected, $collector->getData());
        } elseif ($wrapped instanceof \Traversable) {
            self::assertSame($expected, \iterator_to_array($wrapped, true));
        }
    }
    
    public static function getDataForTestSomeIterableCollectorsAllowToPreserveKeys(): \Generator
    {
        yield 'DefaultCollector' =>     [Collectors::default(),      [4 => 'b', 8 => 'c', 3 => 'a']];
        yield 'SplFixedArray' =>        [new \SplFixedArray(10),     [3 => 'a', 4 => 'b', 8 => 'c']];
        yield 'ArrayIterator' =>        [new \ArrayIterator([]),     [4 => 'b', 8 => 'c', 3 => 'a']];
        yield 'ArrayObject' =>          [new \ArrayObject(),         [4 => 'b', 8 => 'c', 3 => 'a']];
        
        $array = [];
        yield 'array' =>                [Collectors::array($array),  [4 => 'b', 8 => 'c', 3 => 'a']];
    }
    
    /**
     * @dataProvider getDataForTestSomeIterableCollectorsAllowToReindexKeys
     */
    public function test_some_iterable_collectors_allows_to_reindex_keys($wrapped): void
    {
        $collector = Collectors::iterable($wrapped, false);
        
        Stream::from([4 => 'b', 8 => 'c', 3 => 'a'])
            ->collectIn($collector)
            ->run();
        
        $expected = ['b', 'c', 'a'];
        
        if ($wrapped instanceof DefaultCollector) {
            self::assertSame($expected, $wrapped->getData());
        } elseif ($wrapped instanceof \SplFixedArray) {
            self::assertSame($expected, $collector->getData());
        } elseif ($wrapped instanceof \Traversable) {
            self::assertSame($expected, \iterator_to_array($wrapped, true));
        }
        
        $collector->clear();
        $collector->allowKeys(true);
        
        Stream::from([4 => 'b', 8 => 'c', 3 => 'a'])
            ->collectIn($collector, true)
            ->run();
        
        if ($wrapped instanceof DefaultCollector) {
            self::assertSame($expected, $wrapped->getData());
        } elseif ($wrapped instanceof \SplFixedArray) {
            self::assertSame($expected, $collector->getData());
        } elseif ($wrapped instanceof \ArrayIterator) {
            self::assertSame($expected, \iterator_to_array($wrapped, false));
        } elseif ($wrapped instanceof \Traversable) {
            self::assertSame($expected, \iterator_to_array($wrapped, true));
        }
    }
    
    public static function getDataForTestSomeIterableCollectorsAllowToReindexKeys(): \Generator
    {
        yield 'DefaultCollector' =>     [Collectors::default()];
        yield 'SplFixedArray' =>        [new \SplFixedArray(10)];
        yield 'ArrayIterator' =>        [new \ArrayIterator([])];
        yield 'ArrayObject' =>          [new \ArrayObject()];
        
        $array = [];
        yield 'array' => [Collectors::array($array)];
    }
    
    /**
     * @dataProvider getDataForTestSomeAdaptersThrowExceptionWhenKeyIsSet
     */
    public function test_some_adapters_throw_exception_when_key_is_set($splObject): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You cannot keep keys and values in '.\get_class($splObject));
        
        $collector = Collectors::getAdapter($splObject);
        $collector->set(1, 2);
    }
    
    public static function getDataForTestSomeAdaptersThrowExceptionWhenKeyIsSet(): \Generator
    {
        yield 'SplMinHeap'          => [new \SplMinHeap()];
        yield 'SplMaxHeap'          => [new \SplMaxHeap()];
        yield 'SplStack'            => [new \SplStack()];
        yield 'SplQueue'            => [new \SplQueue()];
        yield 'SplDoublyLinkedList' => [new \SplDoublyLinkedList()];
    }
    
    public function test_SplPriorityQueueAdapter_collector_allows_to_set_priority_of_inserted_items(): void
    {
        $queue = new \SplPriorityQueue();
        $collector = Collectors::wrapSplPriorityQueue($queue, false);
        
        $collector->setPriority(2);
        Stream::from(['a', 'b'])->collectIn($collector)->run();
        
        $collector->setPriority(1);
        Stream::from([1, 2])->collectIn($collector)->run();
        
        $collector->setPriority(3);
        Stream::from([5, 4])->collectIn($collector)->run();
        
        $collector->setPriority(0);
        Stream::from(['o', 'p'])->collectIn($collector)->run();
        
        self::assertSame([
            7 => 5,
            6 => 4,
            5 => 'a',
            4 => 'b',
            3 => 2,
            2 => 1,
            1 => 'p',
            0 => 'o',
        ], \iterator_to_array($queue));
    }
    
    public function test_SplPriorityQueueAdapter_collector_allows_to_change_priority_of_inserted_items(): void
    {
        $queue = new \SplPriorityQueue();
        $collector = Collectors::wrapSplPriorityQueue($queue, false);
        
        Stream::from(['o', 'p'])->collectIn($collector)->run();
        
        $collector->increasePriority();
        Stream::from([1, 2])->collectIn($collector)->run();
        
        $collector->increasePriority(2);
        Stream::from([5, 4])->collectIn($collector)->run();
        
        $collector->decreasePriority();
        Stream::from(['a', 'b'])->collectIn($collector)->run();
        
        self::assertSame(2, $collector->getPriority());
        
        self::assertSame([
            7 => 5,
            6 => 4,
            5 => 'b',
            4 => 'a',
            3 => 2,
            2 => 1,
            1 => 'o',
            0 => 'p',
        ], \iterator_to_array($queue));
    }
    
    public function test_for_many_collectors_default_behaviour_if_keys_are_preserved_can_be_change(): void
    {
        $data = [5 => 'a', 6 => 4, 1 => 'p', 2 => 1];
        
        $collector = Collectors::default();
        
        Stream::from($data)->collectIn($collector)->run();
        self::assertSame($data, $collector->getData());
        
        $collector->clear();
        $collector->allowKeys(false);
        
        Stream::from($data)->collectIn($collector)->run();
        self::assertSame(\array_values($data), $collector->getData());
    }
}