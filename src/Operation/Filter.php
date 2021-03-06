<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Filter\Filter as FilterStrategy;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Filter extends BaseOperation
{
    private FilterStrategy $filterStrategy;
    
    private bool $negation;
    private int $mode;
    
    /**
     * @param FilterStrategy|Predicate|callable|mixed $filter
     * @param bool $negation
     * @param int $mode
     */
    public function __construct($filter, bool $negation = false, int $mode = Check::VALUE)
    {
        $this->filterStrategy = Filters::getAdapter($filter);
        $this->negation = $negation;
        $this->mode = Check::getMode($mode);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->negation
            XOR $this->filterStrategy->isAllowed($signal->item->value, $signal->item->key, $this->mode)
        ) {
            $this->next->handle($signal);
        }
    }
    
    public function filterData(): FilterData
    {
        return new FilterData($this->filterStrategy, $this->negation, $this->mode);
    }
}