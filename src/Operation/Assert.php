<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\AssertionFailed;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Assert extends BaseOperation
{
    private Filter $filter;
    
    private int $mode;
    
    /**
     * @param Filter|callable|mixed $filter
     * @param int $mode
     */
    public function __construct($filter, int $mode = Check::VALUE)
    {
        $this->filter = Filters::getAdapter($filter);
        $this->mode = Check::getMode($mode);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key, $this->mode)) {
            $this->next->handle($signal);
        } else {
            throw AssertionFailed::exception($signal->item->value, $signal->item->key, $this->mode);
        }
    }
}