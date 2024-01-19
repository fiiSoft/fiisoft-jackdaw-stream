<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SkipWhile extends BaseOperation
{
    private Filter $filter;
    
    private bool $doWhile, $isActive = true;
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function __construct($filter, ?int $mode = null, bool $until = false)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
        $this->doWhile = !$until;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->doWhile XOR $this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->next->handle($signal);
            $signal->forget($this);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->doWhile XOR $this->filter->isAllowed($value, $key)) {
                    $this->isActive = false;
                } else {
                    continue;
                }
            }
            
            yield $key => $value;
        }
    }
    
    public function shouldBeInversed(): bool
    {
        return $this->filter instanceof FilterNOT;
    }
    
    public function createInversed(): self
    {
        if ($this->shouldBeInversed()) {
            return new self($this->filter->negate(), $this->filter->getMode(), !$this->doWhile);
        }
        
        throw OperationExceptionFactory::cannotInverseOperation();
    }
}