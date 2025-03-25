<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\FilterData;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\StreamBuilder;

final class FilterFieldData extends BaseFilterData implements StreamBuilder
{
    /** @var string|int */
    public $field;
    
    /**
     * @param string|int $field
     */
    public function __construct($field, Filter $filter, bool $negation)
    {
        parent::__construct($filter, $negation);
        
        $this->field = $field;
    }
    
    public function mergeWith(BaseFilterData $other): bool
    {
        return $other instanceof self
            && $this->field === $other->field
            && parent::mergeWith($other);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        if ($this->negation) {
            foreach ($stream as $key => $value) {
                if ($this->filter->isAllowed($value[$this->field], $key)) {
                    continue;
                }
                
                yield $key => $value;
            }
        } else {
            foreach ($stream as $key => $value) {
                if ($this->filter->isAllowed($value[$this->field], $key)) {
                    yield $key => $value;
                }
            }
        }
    }
}