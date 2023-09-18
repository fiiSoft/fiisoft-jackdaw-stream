<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Specs;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;

final class SingleComparison extends Comparison
{
    private ?Comparator $comparator;
    private int $mode;
    
    /**
     * @param Comparable|callable|null $comparator
     */
    public function __construct($comparator = null, int $mode = Check::VALUE)
    {
        $this->comparator = Comparators::getAdapter($comparator);
        $this->mode = Check::getMode($mode);
    }
    
    public function comparator(): ?Comparator
    {
        return $this->comparator;
    }
    
    public function mode(): int
    {
        return $this->mode;
    }
    
    public function getComparators(): array
    {
        return [$this->comparator];
    }
}