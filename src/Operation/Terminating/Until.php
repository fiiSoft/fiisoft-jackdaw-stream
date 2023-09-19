<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Until extends BaseOperation
{
    private Filter $filter;
    private int $mode;
    private bool $doWhile;
    
    /**
     * @param Filter|callable|mixed $filter
     * @param int $mode
     * @param bool $doWhile
     */
    public function __construct($filter, int $mode = Check::VALUE, bool $doWhile = false)
    {
        $this->filter = Filters::getAdapter($filter);
        $this->mode = $mode;
        $this->doWhile = $doWhile;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->doWhile XOR $this->filter->isAllowed($signal->item->value, $signal->item->key, $this->mode)) {
            $signal->limitReached($this);
        } else {
            $this->next->handle($signal);
        }
    }
    
    public function canBeInversed(): bool
    {
        return $this->filter instanceof FilterNOT;
    }
    
    public function createInversed(): self
    {
        if ($this->filter instanceof FilterNOT) {
            return new self($this->filter->getFilter(), $this->mode, !$this->doWhile);
        }
        
        throw new \LogicException('Cannot create inversed operation');
    }
}