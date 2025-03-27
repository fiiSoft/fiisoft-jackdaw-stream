<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Specs;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;

final class SingleComparison extends Comparison
{
    private ?Comparator $comparator;
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    protected function __construct($comparator = null, int $mode = Check::VALUE)
    {
        parent::__construct($mode);
        
        $this->comparator = Comparators::getAdapter($comparator);
    }
    
    public function comparator(): ?Comparator
    {
        return $this->comparator;
    }
    
    public function getComparators(): array
    {
        return [$this->comparator];
    }
}