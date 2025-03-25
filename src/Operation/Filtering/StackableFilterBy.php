<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterFieldData;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class StackableFilterBy extends BaseOperation
{
    protected Filter $filter;
    
    /** @var string|int */
    protected $field;
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function __construct($field, $filter)
    {
        $this->field = Helper::validField($field, 'field');
        $this->filter = Filters::getAdapter($filter, Check::VALUE);
    }
    
    abstract public function filterByData(): FilterFieldData;
}