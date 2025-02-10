<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Until extends BaseOperation
{
    private Filter $filter;
    
    private bool $doWhile;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($filter, ?int $mode = null, bool $doWhile = false)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
        $this->doWhile = $doWhile;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->doWhile XOR $this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $signal->limitReached($this);
        } else {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->doWhile XOR $this->filter->isAllowed($value, $key)) {
                break;
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