<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

final class ReverseComparator implements Comparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return $value2 <=> $value1;
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return $value2 <=> $value1 ?: $key2 <=> $key1;
    }
}