<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare\Set;

use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Time\Compare\IdleTimeComp;
use FiiSoft\Jackdaw\Filter\Time\Compare\SetTimeComp;
use FiiSoft\Jackdaw\Filter\Time\Compare\TimeComparator;

final class NotInSet extends SetTimeComp
{
    /**
     * @inheritDoc
     */
    public function isSatisfiedBy($time): bool
    {
        if ($time instanceof \DateTimeInterface) {
            $key = $time;
        } elseif (\is_string($time)) {
            $key = new \DateTimeImmutable($time);
        } else {
            throw FilterExceptionFactory::invalidTimeValue($time);
        }
        
        return !isset($this->dates[$key->format('c')]);
    }
    
    public function negation(): TimeComparator
    {
        $negation = new InSet([]);
        $negation->dates = $this->dates;
        
        return $negation;
    }
    
    public function optimise(): TimeComparator
    {
        return empty($this->dates) ? IdleTimeComp::true() : $this;
    }
}