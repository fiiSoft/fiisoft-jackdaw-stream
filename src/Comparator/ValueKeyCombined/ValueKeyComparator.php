<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ValueKeyCombined;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;

abstract class ValueKeyComparator implements Comparator
{
    protected Comparator $valueComparator;
    protected Comparator $keyComparator;
    
    /**
     * @param Comparable|callable|null $valueComparator
     * @param Comparable|callable|null $keyComparator
     */
    public function __construct($valueComparator = null, $keyComparator = null)
    {
        $this->valueComparator = Comparators::prepare($valueComparator);
        $this->keyComparator = Comparators::prepare($keyComparator);
    }
    
    /**
     * @inheritDoc
     */
    final public function compare($value1, $value2): int
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
    
    final public function comparator(): Comparator
    {
        return $this;
    }
    
    final public function mode(): int
    {
        return Check::BOTH;
    }
}