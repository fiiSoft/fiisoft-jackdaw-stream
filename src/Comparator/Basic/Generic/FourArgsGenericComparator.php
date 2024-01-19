<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic\Generic;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;
use FiiSoft\Jackdaw\Internal\Check;

final class FourArgsGenericComparator extends GenericComparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        throw ComparatorExceptionFactory::cannotCompareTwoValues();
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return ($this->comparator)($value1, $value2, $key1, $key2);
    }
    
    public function mode(): int
    {
        return Check::BOTH;
    }
    
    public function isFullAssoc(): bool
    {
        return true;
    }
}