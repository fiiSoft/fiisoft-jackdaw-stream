<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

final class FilterBy extends SingleFilterHolder
{
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field valid key in array
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($field, $filter)
    {
        parent::__construct(Filters::getAdapter($filter, Check::VALUE), Check::VALUE);
        
        $this->field = Helper::validField($field, 'field');
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed($value[$this->field], $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value[$this->field], $key)) {
                yield $key => $value;
            }
        }
    }
    
    public function negate(): Filter
    {
        return new self($this->field, $this->filter->negate());
    }
    
    public function inMode(?int $mode): self
    {
        return $this;
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->field === $this->field
            && parent::equals($other);
    }
    
    protected function createFilter(Filter $filter): Filter
    {
        return new self($this->field, $filter);
    }
}