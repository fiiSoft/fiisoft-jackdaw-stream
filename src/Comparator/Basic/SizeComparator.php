<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

/**
 * Allows to compare size of arrays or \Countable objects.
 */
final class SizeComparator extends BaseComparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return \count($value1) <=> \count($value2);
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return \count($value1) <=> \count($value2) ?: $key1 <=> $key2;
    }
}