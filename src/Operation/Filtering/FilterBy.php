<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Internal\FilterByData;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterBy extends BaseOperation
{
    private Filter $filter;
    
    private bool $negation;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($field, $filter, bool $negation = false)
    {
        $this->field = Helper::validField($field, 'field');
        $this->filter = Filters::getAdapter($filter, Check::VALUE);
        $this->negation = $negation;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->negation XOR $this->filter->isAllowed($signal->item->value[$this->field], $signal->item->key)) {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->negation XOR $this->filter->isAllowed($value[$this->field], $key)) {
                yield $key => $value;
            }
        }
    }
    
    public function filterByData(): FilterByData
    {
        return new FilterByData($this->field, $this->filter, $this->negation);
    }
}