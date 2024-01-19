<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic\Generic;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Internal\Check;

final class TwoArgsGenericComparator extends GenericComparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return ($this->comparator)($value1, $value2);
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return ($this->comparator)($value1, $value2) ?: ($this->comparator)($key1, $key2);
    }
    
    public function mode(): int
    {
        return Check::VALUE;
    }
    
    public function isFullAssoc(): bool
    {
        return false;
    }
}