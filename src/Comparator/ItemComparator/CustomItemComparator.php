<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Comparator\Comparator;

abstract class CustomItemComparator implements ItemComparator
{
    protected Comparator $comparator;
    
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }
}