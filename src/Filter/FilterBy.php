<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

final class FilterBy extends BaseFilter
{
    private Filter $filter;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field valid key in array
     * @param FilterReady|callable|mixed $filter
     */
    public static function create($field, $filter): self
    {
        return new self(Helper::validField($field, 'field'), Filters::getAdapter($filter, Check::VALUE));
    }
    
    /**
     * @param string|int $field valid key in array
     */
    private function __construct($field, Filter $filter)
    {
        parent::__construct(Check::VALUE);
        
        $this->field = $field;
        $this->filter = $filter;
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
        return $other instanceof $this
            && $other->field === $this->field
            && $other->filter->equals($this->filter)
            && parent::equals($other);
    }
}