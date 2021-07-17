<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

final class DefaultComparator implements Comparator
{
    public function compare($value1, $value2): int
    {
        return $value1 <=> $value2;
    }
    
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return $value1 <=> $value2 ?: $key1 <=> $key2;
    }
}