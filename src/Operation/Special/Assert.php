<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Special\Assert\AssertionFailed;

final class Assert extends BaseOperation
{
    private Filter $filter;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($filter, ?int $mode = null)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->next->handle($signal);
        } else {
            throw AssertionFailed::exception($signal->item->value, $signal->item->key, $this->filter->getMode());
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value, $key)) {
                yield $key => $value;
            } else {
                throw AssertionFailed::exception($value, $key, $this->filter->getMode());
            }
        }
    }
}