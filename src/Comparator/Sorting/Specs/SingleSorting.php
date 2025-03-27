<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Sorting\Specs;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

final class SingleSorting extends Sorting
{
    private ?Comparator $comparator;
    private bool $reversed;
    private int $mode;
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    protected function __construct(
        bool $reversed = false,
        $comparator = null,
        int $mode = Check::VALUE
    ) {
        parent::__construct();
        
        $this->reversed = $reversed;
        $this->mode = Mode::get($mode);
        $this->comparator = Comparators::getAdapter($comparator);
    }
    
    public function comparator(): ?Comparator
    {
        return $this->comparator;
    }
    
    public function mode(): int
    {
        return $this->mode;
    }
    
    public function isReversed(): bool
    {
        return $this->reversed;
    }
    
    public function getReversed(): self
    {
        return new self(!$this->reversed, $this->comparator, $this->mode);
    }
}