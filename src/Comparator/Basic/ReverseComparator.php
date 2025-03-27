<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

final class ReverseComparator extends BaseComparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return \gettype($value2) <=> \gettype($value1) ?: $value2 <=> $value1;
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return \gettype($value2) <=> \gettype($value1) ?: $value2 <=> $value1
            ?: \gettype($key2) <=> \gettype($key1) ?: $key2 <=> $key1;
    }
}