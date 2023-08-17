<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Filter\Filter as FilterStrategy;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\FilterSingle;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Filter extends BaseOperation implements FilterSingle
{
    private FilterStrategy $filter;
    
    private bool $negation;
    private int $mode;
    
    /**
     * @param FilterStrategy|Predicate|callable|mixed $filter
     * @param bool $negation
     * @param int $mode
     */
    public function __construct($filter, bool $negation = false, int $mode = Check::VALUE)
    {
        $this->filter = Filters::getAdapter($filter);
        $this->negation = $negation;
        $this->mode = Check::getMode($mode);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->negation
            XOR $this->filter->isAllowed($signal->item->value, $signal->item->key, $this->mode)
        ) {
            $this->next->handle($signal);
        }
    }
    
    public function filterData(): FilterData
    {
        return new FilterData($this->filter, $this->negation, $this->mode);
    }
}