<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

final class Collectors
{
    /**
     * @param Collector|\ArrayAccess $collector
     * @return Collector
     */
    public static function getAdapter($collector): Collector
    {
        if ($collector instanceof Collector) {
            return $collector;
        }
    
        if ($collector instanceof \ArrayAccess) {
            return new ArrayAccess($collector);
        }
    
        throw new \InvalidArgumentException('Invalid param collector');
    }
    
    public static function default(): \ArrayIterator
    {
        return new \ArrayIterator();
    }
}