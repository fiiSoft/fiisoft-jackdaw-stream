<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare\Point;

use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Time\Compare\PointTimeComp;
use FiiSoft\Jackdaw\Filter\Time\Compare\TimeComparator;

final class After extends PointTimeComp
{
    /**
     * @inheritDoc
     */
    public function isSatisfiedBy($time): bool
    {
        if ($time instanceof \DateTimeInterface) {
            return $time > $this->time;
        }
        
        if (\is_string($time)) {
            return new \DateTimeImmutable($time) > $this->time;
        }
        
        throw FilterExceptionFactory::invalidTimeValue($time);
    }
    
    public function negation(): TimeComparator
    {
        return new Until($this->time);
    }
}