<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ValueKeyCombined;

final class ValueAscKeyAscComparator extends ValueKeyComparator
{
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return $this->valueComparator->compare($value1, $value2) ?: $this->keyComparator->compare($key1, $key2);
    }
}