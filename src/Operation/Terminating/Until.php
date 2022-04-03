<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Until extends BaseOperation
{
    private Filter $filter;
    private int $mode;
    private bool $doWhile;
    
    /**
     * @param Filter|Predicate|callable|mixed $condition
     * @param int $mode
     * @param bool $doWhile
     */
    public function __construct($condition, int $mode = Check::VALUE, bool $doWhile = false)
    {
        $this->filter = Filters::getAdapter($condition);
        $this->mode = $mode;
        $this->doWhile = $doWhile;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->doWhile XOR $this->filter->isAllowed($signal->item->value, $signal->item->key, $this->mode)) {
            $signal->stop();
        } else {
            $this->next->handle($signal);
        }
    }
}