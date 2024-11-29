<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Specs;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

final class DoubleComparison extends Comparison
{
    /** @var ComparatorReady|callable|null */
    private $valueComparator, $keyComparator;
    
    /**
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    protected function __construct(int $mode, $valueComparator = null, $keyComparator = null, bool $isPairComp = false)
    {
        parent::__construct($mode, $isPairComp);
        
        if ($this->mode !== Check::BOTH && $this->mode !== Check::ANY) {
            throw Mode::invalidModeException($this->mode);
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
}