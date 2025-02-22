<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\Conditional;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Logic\ConditionalFilter;

final class ConditionalYesFilter extends ConditionalFilter
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return !$this->condition->isAllowed($value, $key) || $this->filter->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!$this->condition->isAllowed($value, $key) || $this->filter->isAllowed($value, $key)) {
                yield $key => $value;
            }
        }
    }
    
    public function negate(): Filter
    {
        //how it should work is unclear...
        return new ConditionalNotFilter($this->condition, $this->filter);
    }
}