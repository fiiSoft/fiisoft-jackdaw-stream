<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Specs;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;

final class DoubleComparison extends Comparison
{
    /** @var Comparable|callable|null */
    private $valueComparator, $keyComparator;
    
    private int $mode;
    
    /**
     * @param Comparable|callable|null $valueComparator
     * @param Comparable|callable|null $keyComparator
     */
    public function __construct(int $mode, $valueComparator = null, $keyComparator = null)
    {
        $this->mode = Check::getMode($mode);
        
        if ($this->mode !== Check::BOTH && $this->mode !== Check::ANY) {
            throw new \InvalidArgumentException('Invalid param mode');
        }
        
        $this->valueComparator = $valueComparator;
        $this->keyComparator = $keyComparator;
    }
    
    public function getComparators(): array
    {
        return [$this->valueComparator, $this->keyComparator];
    }
    
    public function comparator(): ?Comparator
    {
        return null;
    }
    
    public function mode(): int
    {
        return $this->mode;
    }
}