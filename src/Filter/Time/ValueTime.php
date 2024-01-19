<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time;

final class ValueTime extends TimeFilter
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->comparator->isSatisfiedBy($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->comparator->isSatisfiedBy($value)) {
                yield $key => $value;
            }
        }
    }
}