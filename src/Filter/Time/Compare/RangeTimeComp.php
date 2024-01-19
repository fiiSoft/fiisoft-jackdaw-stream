<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare;

use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;

abstract class RangeTimeComp extends CompoundTimeComp
{
    protected \DateTimeInterface $earlier, $later;
    
    /**
     * @param \DateTimeInterface|string $earlier valid time
     * @param \DateTimeInterface|string $later valid time
     */
    final public function __construct($earlier, $later)
    {
        $this->earlier = $this->prepare($earlier);
        $this->later = $this->prepare($later);
        
        if ($this->earlier > $this->later) {
            throw FilterExceptionFactory::paramFromIsGreaterThanUntil();
        }
    }
}