<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Collector\Adapter\ArrayAccessAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\Iterable\ArrayAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\Iterable\ArrayIteratorAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\Iterable\ArrayObjectAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\Iterable\SplFixedArrayAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\SplDoublyLinkedListAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\SplHeapAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\SplPriorityQueueAdapter;
use FiiSoft\Jackdaw\Exception\InvalidParamException;

/**
 * @template K of string|int
 * @template M of mixed
 */
final class Collectors
{
    /**
     * @param Collector|\ArrayAccess<K,M>|\SplHeap<M>|\SplPriorityQueue<int, M> $collector
     */
    public static function getAdapter($collector, ?bool $allowsKeys = null): Collector
    {
        if ($collector instanceof Collector) {
            return $collector;
        }
        
        if ($collector instanceof \SplFixedArray) {
            return new SplFixedArrayAdapter($collector, $allowsKeys);
        }

        if ($collector instanceof \SplDoublyLinkedList) {
            return new SplDoublyLinkedListAdapter($collector);
        }

        if ($collector instanceof \ArrayAccess) {
            return new ArrayAccessAdapter($collector, $allowsKeys);
        }

        if ($collector instanceof \SplHeap) {
            return new SplHeapAdapter($collector);
        }

        if ($collector instanceof \SplPriorityQueue) {
            return self::wrapSplPriorityQueue($collector, $allowsKeys ?? true);
        }
    
        throw InvalidParamException::describe('collector', $collector);
    }
    
    /**
     * @param IterableCollector|\ArrayIterator<K,M>|\ArrayObject<K,M>|\SplFixedArray<M> $collector
     */
    public static function iterable($collector, ?bool $allowsKeys = null): IterableCollector
    {
        if ($collector instanceof IterableCollector) {
            $collector->allowKeys($allowsKeys);
            return $collector;
        }
        
        if ($collector instanceof \ArrayIterator) {
            return new ArrayIteratorAdapter($collector, $allowsKeys);
        }
        
        if ($collector instanceof \ArrayObject) {
            return new ArrayObjectAdapter($collector, $allowsKeys);
        }
        
        if ($collector instanceof \SplFixedArray) {
            return new SplFixedArrayAdapter($collector, $allowsKeys);
        }
        
        throw InvalidParamException::describe('collector', $collector);
    }
    
    public static function values(): IterableCollector
    {
        return self::default(false);
    }
    
    public static function default(bool $allowsKeys = true): IterableCollector
    {
        return new DefaultCollector([], $allowsKeys);
    }
    
    /**
     * @param array<string|int, mixed> $storage REFERENCE
     * @return IterableCollector<string|int, mixed>
     */
    public static function array(array &$storage, bool $allowsKeys = true): IterableCollector
    {
        return new ArrayAdapter($storage, $allowsKeys);
    }
    
    /**
     * @param \SplPriorityQueue<int, mixed> $queue
     */
    public static function wrapSplPriorityQueue(
        \SplPriorityQueue $queue,
        bool $allowsKeys = true
    ): SplPriorityQueueAdapter
    {
        return new SplPriorityQueueAdapter($queue, $allowsKeys);
    }
}