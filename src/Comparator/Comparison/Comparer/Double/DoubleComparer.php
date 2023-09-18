<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Comparer\Double;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer;

abstract class DoubleComparer implements Comparer
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
}