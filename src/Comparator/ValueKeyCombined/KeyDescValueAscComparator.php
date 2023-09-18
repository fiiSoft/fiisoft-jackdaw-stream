<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ValueKeyCombined;

final class KeyDescValueAscComparator extends ValueKeyComparator
{
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return $this->keyComparator->compare($key2, $key1) ?: $this->valueComparator->compare($value1, $value2);
    }
}