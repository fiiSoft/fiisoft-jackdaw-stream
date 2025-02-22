<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\IntValue;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\BaseFilterAdapter;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class IntValueFilter extends BaseFilterAdapter
{
    private IntValue $intValue;
    
    public function __construct(IntValue $intValue, Filter $filter)
    {
        parent::__construct($filter);
        
        $this->intValue = $intValue;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed($this->intValue->int());
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($this->intValue->int())) {
                yield $key => $value;
            }
        }
    }
    
    public function negate(): Filter
    {
        return new self($this->intValue, $this->filter->negate());
    }
    
    public function equals(Filter $other): bool
    {
        return $other instanceof $this
            && $other->intValue->equals($this->intValue)
            && parent::equals($other);
    }
}