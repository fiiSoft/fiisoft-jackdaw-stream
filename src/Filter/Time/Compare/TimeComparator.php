<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare;

use FiiSoft\Jackdaw\Exception\InvalidParamException;

abstract class TimeComparator
{
    /**
     * @param \DateTimeInterface|string $time
     */
    final protected function prepare($time): \DateTimeInterface
    {
        if ($time instanceof \DateTimeInterface) {
            return $time;
        }
        
        if (\is_string($time)) {
            return new \DateTimeImmutable($time);
        }
        
        throw InvalidParamException::describe('time', $time);
    }
    
    /**
     * @param \DateTimeInterface|string $time
     */
    abstract public function isSatisfiedBy($time): bool;
    
    abstract public function negation(): self;
}