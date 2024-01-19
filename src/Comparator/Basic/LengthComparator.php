<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

final class LengthComparator extends BaseComparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return \mb_strlen($value1) <=> \mb_strlen($value2);
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return \mb_strlen($value1) <=> \mb_strlen($value2) ?: $key1 <=> $key2;
    }
}