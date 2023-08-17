<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ValueKeyCombined;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;

abstract class ValueKeyComparator implements Comparator
{
    protected Comparator $valueComparator;
    protected Comparator $keyComparator;
    
    /**
     * @param Comparator|callable|null $valueComparator
     * @param Comparator|callable|null $keyComparator
     */
    public function __construct($valueComparator = null, $keyComparator = null)
    {
        $this->valueComparator = Comparators::getAdapter($valueComparator) ?? Comparators::default();
        $this->keyComparator = Comparators::getAdapter($keyComparator) ?? Comparators::default();
    }
    
    /**
     * @inheritDoc
     */
    final public function compare($value1, $value2): int
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
}