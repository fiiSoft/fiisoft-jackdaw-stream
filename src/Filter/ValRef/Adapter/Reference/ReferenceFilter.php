<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\Reference;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\BaseFilterAdapter;

final class ReferenceFilter extends BaseFilterAdapter
{
    /** @var mixed REFERENCE */
    private $variable;
    
    /**
     * @param mixed $variable REFERENCE
     */
    public function __construct(&$variable, Filter $filter)
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
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($this->variable)) {
                yield $key => $value;
            }
        }
    }
    
    public function negate(): Filter
    {
        return new self($this->variable, $this->filter->negate());
    }
}