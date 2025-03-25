<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Operation\Operation;

abstract class PossiblyInversible extends BaseOperation
{
    protected Filter $filter;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function __construct($filter, ?int $mode = null)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
    }
    
    final public function createInversed(): ?Operation
    {
        if ($this->filter instanceof FilterNOT) {
            $negation = $this->filter->negate();
            
            return $negation instanceof FilterNOT ? null : $this->inversedOperation($negation);
        }
        
        return null;
    }
    
    abstract protected function inversedOperation(Filter $filter): Operation;
}