<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Internal;

use FiiSoft\Jackdaw\Filter\Filter;

final class FilterByData
{
    public Filter $filter;
    
    public bool $negation;
    
    /** @var string|int */
    public $field;
    
    /**
     * @param string|int $field
     */
    public function __construct($field, Filter $filter, bool $negation = null)
    {
        $this->field = $field;
        $this->filter = $filter;
        $this->negation = $negation;
    }
}