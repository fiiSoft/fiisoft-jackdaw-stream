<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Comparer\Single;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer;

abstract class SingleComparer implements Comparer
{
    protected Comparator $comparator;
    
    public function __construct(?Comparator $comparator)
    {
        $this->comparator = Comparators::prepare($comparator);
    }
}