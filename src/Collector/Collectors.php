<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Collector\Adapter\ArrayAccessAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\Iterable\ArrayIteratorAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\Iterable\ArrayObjectAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\SplDoublyLinkedListAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\Iterable\SplFixedArrayAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\SplHeapAdapter;
use FiiSoft\Jackdaw\Collector\Adapter\SplPriorityQueueAdapter;
use FiiSoft\Jackdaw\Internal\Helper;

final class Collectors
{
    /**
     * @param Collector|\ArrayAccess|\SplHeap|\SplPriorityQueue $collector
     */
    public static function getAdapter($collector, ?bool $allowsKeys = null): Collector
    {
        if ($collector instanceof Collector) {
            $collector->allowKeys($allowsKeys);
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
            return new SplPriorityQueueAdapter($collector, 0, $allowsKeys);
        }
    
        throw Helper::invalidParamException('collector', $collector);
    }
    
    /**
     * @param IterableCollector|\ArrayIterator|\ArrayObject|\SplFixedArray $collector
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
        
        throw Helper::invalidParamException('collector', $collector);
    }
    
    public static function default(bool $allowsKeys = true): IterableCollector
    {
        return new DefaultCollector([], $allowsKeys);
    }
    
    public static function values(): IterableCollector
    {
        return new DefaultCollector([], false);
    }
    
    public static function wrapSplPriorityQueue(
        \SplPriorityQueue $queue,
        bool $allowsKeys = true
    ): SplPriorityQueueAdapter
    {
        return new SplPriorityQueueAdapter($queue, 0, $allowsKeys);
    }
}