<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\Reference;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\BaseStringFilterAdapter;

final class ReferenceStringFilter extends BaseStringFilterAdapter
{
    /** @var mixed REFERENCE */
    private $variable;
    
    /**
     * @param mixed $variable REFERENCE
     */
    public function __construct(&$variable, StringFilter $filter)
    {
        parent::__construct($filter);
        
        $this->variable = &$variable;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed($this->variable);
    }
    
    /**
     * @inheritDoc
     */
    protected function iterateStream(StringFilter $filter, iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($filter->isAllowed($this->variable)) {
                yield $key => $value;
            }
        }
    }
    
    public function negate(): self
    {
        return new self($this->variable, $this->filter->negate());
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this;
    }
}