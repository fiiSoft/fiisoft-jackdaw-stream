<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Until extends BaseOperation
{
    /** @var Filter */
    private $filter;
    
    /** @var int */
    private $mode;
    
    /** @var bool */
    private $doWhile;
    
    /**
     * @param Filter|callable|mixed $condition
     * @param int $mode
     * @param bool $doWhile
     */
    public function __construct($condition, int $mode = Check::VALUE, bool $doWhile = false)
    {
        $this->filter = Filters::getAdapter($condition);
        $this->mode = $mode;
        $this->doWhile = $doWhile;
    }
    
    public function handle(Signal $signal)
    {
        if ($this->doWhile XOR $this->filter->isAllowed($signal->item->value, $signal->item->key, $this->mode)) {
            $signal->terminate();
        } else {
            $this->next->handle($signal);
        }
    }
}