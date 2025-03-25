<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\Conditional;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Logic\ConditionalFilter;

final class ConditionalNotFilter extends ConditionalFilter
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return !$this->condition->isAllowed($value, $key) || !$this->filter->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->condition->isAllowed($value, $key) && $this->filter->isAllowed($value, $key)) {
                continue;
            }
            
            yield $key => $value;
        }
    }
    
    public function negate(): Filter
    {
        //how it should work is unclear...
        return new ConditionalYesFilter($this->condition, $this->filter);
    }
}