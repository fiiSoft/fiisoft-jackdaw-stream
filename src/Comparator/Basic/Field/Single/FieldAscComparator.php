<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic\Field\Single;

use FiiSoft\Jackdaw\Comparator\Basic\Field\SingleFieldComparator;

final class FieldAscComparator extends SingleFieldComparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return  \gettype($value1[$this->field]) <=> \gettype($value2[$this->field])
            ?: $value1[$this->field] <=> $value2[$this->field];
    }
}