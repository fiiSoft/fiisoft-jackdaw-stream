<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

final class DefaultComparator extends BaseComparator
{
    public function compare($value1, $value2): int
    {
        return \gettype($value1) <=> \gettype($value2) ?: $value1 <=> $value2;
    }
    
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return \gettype($value1) <=> \gettype($value2)
            ?: $value1 <=> $value2
            ?: \gettype($key1) <=> \gettype($key2)
            ?: $key1 <=> $key2;
    }
}