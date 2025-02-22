<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\FilterData;

use FiiSoft\Jackdaw\Filter\Filter;

final class FilterFieldData extends BaseFilterData
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
}