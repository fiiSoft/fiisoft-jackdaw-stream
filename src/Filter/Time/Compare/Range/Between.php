<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare\Range;

use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\Is;
use FiiSoft\Jackdaw\Filter\Time\Compare\RangeTimeComp;
use FiiSoft\Jackdaw\Filter\Time\Compare\TimeComparator;

final class Between extends RangeTimeComp
{
    /**
     * @inheritDoc
     */
    public function isSatisfiedBy($time): bool
    {
        if ($time instanceof \DateTimeInterface) {
            return $time >= $this->earlier && $time <= $this->later;
        }
        
        if (\is_string($time)) {
            $time = new \DateTimeImmutable($time);
            
            return $time >= $this->earlier && $time <= $this->later;
        }
        
        throw FilterExceptionFactory::invalidTimeValue($time);
    }
    
    public function negation(): TimeComparator
    {
        return new Outside($this->earlier, $this->later);
    }
    
    public function optimise(): TimeComparator
    {
        return $this->earlier == $this->later ? new Is($this->earlier) : $this;
    }
}